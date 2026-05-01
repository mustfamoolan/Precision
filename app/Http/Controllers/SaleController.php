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
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
        } else {
            // Default to current month
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $query->whereBetween('date', [$start, $end]);
        }

        // Additional legacy filters if still used
        if ($request->get('filter') === 'week') {
            $query->where('date', '>=', Carbon::now()->startOfWeek());
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

        $sales = $query->with(['payments.bank', 'bank'])->latest('date')->get();
        
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
            'filters' => array_merge($request->all(['filter', 'search', 'type']), [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'status' => $request->get('status', 'all'),
            ]),
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
        
        if ($validated['container_number'] && !str_starts_with($validated['container_number'], 'CN-')) {
            $validated['container_number'] = 'CN-' . $validated['container_number'];
        }
        $validated['due_amount'] = $validated['amount'] - $validated['paid_amount'];
        
        if ($validated['due_amount'] <= 0) {
            $validated['status'] = 'paid';
        } elseif ($validated['paid_amount'] > 0) {
            $validated['status'] = 'partial';
        } else {
            $validated['status'] = 'pending';
        }

        $sale = \App\Models\Sale::withoutEvents(function () use ($validated) {
            return \App\Models\Sale::create($validated);
        });

        // If initial payment exists, create a SalePayment record
        if ($sale->paid_amount > 0 && $sale->bank_id) {
            \App\Models\SalePayment::create([
                'sale_id' => $sale->id,
                'bank_id' => $sale->bank_id,
                'amount' => $sale->paid_amount,
                'date' => $sale->date,
                'note' => 'Initial payment upon creation'
            ]);
        }

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

        if ($validated['container_number'] && !str_starts_with($validated['container_number'], 'CN-')) {
            $validated['container_number'] = 'CN-' . $validated['container_number'];
        }
        $validated['due_amount'] = $validated['amount'] - $validated['paid_amount'];
        
        if ($validated['due_amount'] <= 0) {
            $validated['status'] = 'paid';
        } elseif ($validated['paid_amount'] > 0) {
            $validated['status'] = 'partial';
        } else {
            $validated['status'] = 'pending';
        }

        \App\Models\Sale::withoutEvents(function () use ($sale, $validated) {
            $sale->update($validated);
        });

        return redirect()->back();
    }

    public function storePayment(Request $request, Sale $sale)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'bank_id' => 'required|exists:banks,id',
            'payment_date' => 'required|date',
        ]);

        // Create individual payment record (this updates bank balance and records transaction)
        \App\Models\SalePayment::create([
            'sale_id' => $sale->id,
            'bank_id' => $request->bank_id,
            'amount' => $request->payment_amount,
            'date' => $request->payment_date,
        ]);

        // Update the sale model totals
        $sale->paid_amount += $request->payment_amount;
        $sale->bank_id = $request->bank_id; // Set last used bank
        $sale->due_amount = $sale->amount - $sale->paid_amount;

        if ($sale->due_amount <= 0) {
            $sale->status = 'paid';
            $sale->due_amount = 0;
            $sale->paid_amount = $sale->amount;
        } elseif ($sale->paid_amount > 0) {
            $sale->status = 'partial';
        } else {
            $sale->status = 'pending';
        }

        // Use withoutEvents to prevent the SaleObserver from overwriting the history with one total transaction
        \App\Models\Sale::withoutEvents(function () use ($sale) {
            $sale->save();
        });

        return redirect()->back();
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->back();
    }
}
