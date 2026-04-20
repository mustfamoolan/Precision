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
                  ->orWhere('invoice_number', 'like', '%' . $request->search . '%');
            });
        }

        $sales = $query->latest('date')->get();
        
        // Summary Data
        $totalSales = $query->sum('amount');
        $thisMonthSales = Sale::whereMonth('date', Carbon::now()->month)
                             ->whereYear('date', Carbon::now()->year)
                             ->sum('amount');

        return Inertia::render('Sales', [
            'sales' => $sales,
            'total_sales_period' => $totalSales,
            'total_sales_month' => $thisMonthSales,
            'filters' => $request->all(['filter', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'invoice_number' => 'required|string',
            'customer_name' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        Sale::create($validated);

        return redirect()->back();
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->back();
    }
}
