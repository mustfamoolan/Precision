<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Bank;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        
        // Revenue (Sales) this month
        $monthlySales = Sale::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');
            
        // Expenses this month
        $monthlyExpenses = Expense::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');
            
        // Net Profit
        $netProfit = $monthlySales - $monthlyExpenses;
        
        // All Bank Balances
        $banks = Bank::all(['id', 'name', 'balance']);
        $totalBankCash = $banks->sum('balance');
        
        // Latest 5 Sales
        $recentSales = Sale::latest('date')
            ->limit(5)
            ->get();
            
        // Growth calc
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthSales = Sale::whereMonth('date', $lastMonth->month)
            ->whereYear('date', $lastMonth->year)
            ->sum('amount');
            
        $salesGrowth = 0;
        if ($lastMonthSales > 0) {
            $salesGrowth = (($monthlySales - $lastMonthSales) / $lastMonthSales) * 100;
        }

        // --- CHART DATA ---
        
        // 1. Last 7 Days Sales Trend
        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now()->subDays($i)->format('D');
            $amount = Sale::whereDate('date', $date)->sum('amount');
            $dailySales[] = [
                'day' => $dayName,
                'amount' => (float)$amount,
            ];
        }

        // 2. Expense Distribution (Top 5 descriptions)
        $expenseBreakdown = Expense::select('description', DB::raw('SUM(amount) as total'))
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->groupBy('description')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'monthly_sales' => $monthlySales,
                'monthly_expenses' => $monthlyExpenses,
                'net_profit' => $netProfit,
                'total_bank_cash' => $totalBankCash,
                'sales_growth' => round($salesGrowth, 1),
            ],
            'chart_data' => [
                'daily_sales' => $dailySales,
                'expense_breakdown' => $expenseBreakdown,
            ],
            'banks' => $banks,
            'recent_sales' => $recentSales,
        ]);
    }
}
