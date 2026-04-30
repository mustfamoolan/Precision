<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Bank;
use Carbon\Carbon;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::query();

        // Type filtering (default to local if not specified, but for 'Sales' page we want local)
        // If on 'EXP INV' page, we'll pass type=export
        $type = $request->get('type', 'local');
        $query->where('type', $type);

        // Filtering Logic
        if ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->get('filter') === 'week') {
            $query->where('date', '>=', Carbon::now()->startOfWeek());
        } elseif ($request->get('filter') === 'month') {
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('invoice_number', 'like', '%' . $request->search . '%')
                  ->orWhere('container_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sales = $query->latest('date')->get();
        
        // Summary Data for the current view
        $totalAmount = $query->sum('amount');
        $totalPaid = $query->sum('paid_amount');
        $totalPending = $query->whereIn('status', ['pending', 'partial'])->sum('due_amount');
        $totalOverdue = $query->where('status', 'overdue')->sum('due_amount'); // Assuming status logic handles this

        return Inertia::render($type === 'export' ? 'ExpInv' : 'Sales', [
            'sales' => $sales,
            'summary' => [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
                'total_overdue' => $totalOverdue,
                'total_count' => $sales->count(),
            ],
            'filters' => $request->all(['filter', 'search', 'start_date', 'end_date', 'type']),
            'banks' => Bank::all(['id', 'name']),
            'customers' => \App\Models\Customer::all(['id', 'name']),
            'local_invoices' => Sale::where('type', 'local')->get(['id', 'invoice_number']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'invoice_number' => 'required|string',
            'customer_name' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|string|in:local,export',
            'items_count' => 'nullable|integer',
            'paid_amount' => 'nullable|numeric',
            'container_number' => 'nullable|string',
            'shipping_status' => 'nullable|string',
            'bank_id' => 'nullable|exists:banks,id',
        ]);

        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $validated['due_amount'] = $validated['amount'] - $validated['paid_amount'];
        
        if ($validated['due_amount'] <= 0) {
            $validated['status'] = 'paid';
        } elseif ($validated['paid_amount'] > 0) {
            $validated['status'] = 'partial';
        } else {
            $validated['status'] = 'pending';
        }

        Sale::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'invoice_number' => 'required|string',
            'customer_name' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|string|in:local,export',
            'items_count' => 'nullable|integer',
            'paid_amount' => 'nullable|numeric',
            'container_number' => 'nullable|string',
            'shipping_status' => 'nullable|string',
            'bank_id' => 'nullable|exists:banks,id',
        ]);

        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $validated['due_amount'] = $validated['amount'] - $validated['paid_amount'];
        
        if ($validated['due_amount'] <= 0) {
            $validated['status'] = 'paid';
        } elseif ($validated['paid_amount'] > 0) {
            $validated['status'] = 'partial';
        } else {
            $validated['status'] = 'pending';
        }

        $sale->update($validated);

        return redirect()->back();
    }

    public function storePayment(Request $request, Sale $sale)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01'
        ]);

        $sale->paid_amount += $request->payment_amount;
        $sale->due_amount = $sale->amount - $sale->paid_amount;

        if ($sale->due_amount <= 0) {
            $sale->status = 'paid';
            $sale->due_amount = 0; // Prevent negative due
            $sale->paid_amount = $sale->amount; // Cap paid amount
        } elseif ($sale->paid_amount > 0) {
            $sale->status = 'partial';
        } else {
            $sale->status = 'pending';
        }

        $sale->save();

        return redirect()->back();
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->back();
    }
}
