<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
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

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->back();
    }
}
