<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Bank;
use App\Models\Shipment;
use App\Models\Cheque;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            // Default to current month (resets on the 1st of every month)
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        $now = Carbon::now();
        $today = Carbon::today();
        
        // Revenue (Sales) in period
        $monthlySales = Sale::whereBetween('date', [$start, $end])
            ->sum('amount');
            
        // Expenses in period
        $monthlyExpenses = Expense::whereBetween('date', [$start, $end])
            ->sum('amount');
            
        // Net Profit
        $netProfit = $monthlySales - $monthlyExpenses;
        
        // All Bank Balances (Real-time, usually not filtered by date unless requested, but let's keep it as is for liquidity)
        $banks = Bank::all(['id', 'name', 'balance']);
        $totalBankCash = $banks->sum('balance');
        $bankLiquidity = $banks->filter(fn($b) => !str_contains(strtolower($b->name), 'cash'))->sum('balance');
        $cashBalance = $banks->filter(fn($b) => str_contains(strtolower($b->name), 'cash'))->sum('balance');
        
        // --- NEW KPIs ---
        $activeShipments = Shipment::whereNotIn('status', ['Completed', 'Delivered'])->count();
        $upcomingCheques = Cheque::where('status', 'pending')
            ->whereBetween('due_date', [$today, $today->copy()->addDays(5)])
            ->count();
            
        // Latest 10 Sales
        $recentSales = Sale::latest('date')
            ->limit(10)
            ->get();
            
        // Latest 10 Expenses
        $recentExpenses = Expense::latest('date')
            ->limit(10)
            ->get();
            
        // Growth calc (Compared to previous period)
        $periodDays = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($periodDays);
        $prevEnd = $start->copy()->subDay();

        $prevSales = Sale::whereBetween('date', [$prevStart, $prevEnd])
            ->sum('amount');
            
        $salesGrowth = 0;
        if ($prevSales > 0) {
            $salesGrowth = (($monthlySales - $prevSales) / $prevSales) * 100;
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'monthly_sales' => $monthlySales,
                'monthly_expenses' => $monthlyExpenses,
                'net_profit' => $netProfit,
                'total_bank_cash' => $totalBankCash,
                'bank_liquidity' => $bankLiquidity,
                'cash_balance' => $cashBalance,
                'sales_growth' => round($salesGrowth, 1),
                'active_shipments' => $activeShipments,
                'upcoming_cheques' => $upcomingCheques,
                'filters' => [
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                ]
            ],
            'banks' => $banks,
            'recent_sales' => $recentSales,
            'recent_expenses' => $recentExpenses,
        ]);
    }
}
