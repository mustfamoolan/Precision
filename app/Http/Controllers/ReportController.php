<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Expense;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Handle Filters
        if ($request->filled(['start_date', 'end_date'])) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
        } elseif ($request->get('filter') === 'week') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } elseif ($request->get('filter') === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        // Summary Calculations
        $totalSales = Sale::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $salesCount = Sale::whereBetween('date', [$startDate, $endDate])->count();
        
        $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $expensesCount = Expense::whereBetween('date', [$startDate, $endDate])->count();
        
        $netProfit = $totalSales - $totalExpenses;

        // Top Expense Categories
        $topExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->select('description', DB::raw('SUM(amount) as total'))
            ->groupBy('description')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Full Ledger Data
        $salesFeed = Sale::whereBetween('date', [$startDate, $endDate])
            ->select('date', 'customer_name as name', 'amount', DB::raw("'sale' as type"))
            ->get();
            
        $expensesFeed = Expense::with('employee')->whereBetween('date', [$startDate, $endDate])
            ->select('date', 'description as name', 'amount', 'employee_id', DB::raw("'expense' as type"))
            ->get()
            ->map(function($e) {
                $e->name = $e->name . " (" . ($e->employee->name ?? 'System') . ")";
                return $e;
            });

        $ledger = $salesFeed->concat($expensesFeed)->sortByDesc('date')->values();

        return Inertia::render('Reports', [
            'summary' => [
                'total_sales' => $totalSales,
                'sales_count' => $salesCount,
                'total_expenses' => $totalExpenses,
                'expenses_count' => $expensesCount,
                'net_profit' => $netProfit,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'period_label' => $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            ],
            'top_expenses' => $topExpenses,
            'ledger' => $ledger,
            'filters' => $request->all(['filter', 'start_date', 'end_date']),
        ]);
    }
}
