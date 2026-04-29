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
        $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        
        // --- CASH FLOW (Monthly Breakdown for last 6 months) ---
        $cashFlow = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = Carbon::now()->subMonths($i)->startOfMonth();
            $mEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthName = $mStart->format('M');
            
            $in = Sale::whereBetween('date', [$mStart, $mEnd])->sum('paid_amount');
            $out = Expense::whereBetween('date', [$mStart, $mEnd])->sum('amount');
            
            $cashFlow[] = [
                'month' => $monthName,
                'inflow' => (float)$in,
                'outflow' => (float)$out,
                'net' => (float)($in - $out)
            ];
        }

        // --- DEBT AGING SUMMARY ---
        $today = Carbon::today();
        $aging = [
            'total_receivable' => Sale::sum('due_amount'),
            'current' => Sale::where('date', '>=', $today->copy()->subDays(30))->sum('due_amount'),
            '30_60_days' => Sale::whereBetween('date', [$today->copy()->subDays(60), $today->copy()->subDays(31)])->sum('due_amount'),
            '60_90_days' => Sale::whereBetween('date', [$today->copy()->subDays(90), $today->copy()->subDays(61)])->sum('due_amount'),
            'over_90_days' => Sale::where('date', '<', $today->copy()->subDays(90))->sum('due_amount'),
        ];

        // Top Expense Categories
        $topExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->select('description', DB::raw('SUM(amount) as total'))
            ->groupBy('description')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Full Ledger Data
        $salesFeed = Sale::whereBetween('date', [$startDate, $endDate])
            ->select('date', 'customer_name as name', 'amount', 'paid_amount', 'due_amount', DB::raw("'sale' as type"))
            ->get();
            
        $expensesFeed = Expense::with('employee')->whereBetween('date', [$startDate, $endDate])
            ->select('date', 'description as name', 'amount', 'employee_id', DB::raw("'expense' as type"))
            ->get()
            ->map(function($e) {
                $e->name = $e->name . " (" . ($e->employee->name ?? 'System') . ")";
                $e->paid_amount = $e->amount;
                $e->due_amount = 0;
                return $e;
            });

        $ledger = $salesFeed->concat($expensesFeed)->sortByDesc('date')->values();

        return Inertia::render('Reports', [
            'summary' => [
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'net_profit' => $totalSales - $totalExpenses,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'period_label' => $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            ],
            'cash_flow' => $cashFlow,
            'aging' => $aging,
            'top_expenses' => $topExpenses,
            'ledger' => $ledger,
            'filters' => $request->all(['filter', 'start_date', 'end_date']),
        ]);
    }
}
