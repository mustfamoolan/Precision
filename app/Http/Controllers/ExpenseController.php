<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Employee;
use Carbon\Carbon;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['employee']);

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
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('employee', function($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $expenses = $query->latest('date')->get();
        
        // Summary Data
        $totalExpenses = $query->sum('amount');
        $thisMonthExpenses = Expense::whereMonth('date', Carbon::now()->month)
                                  ->whereYear('date', Carbon::now()->year)
                                  ->sum('amount');

        return Inertia::render('Expenses', [
            'expenses' => $expenses,
            'employees' => Employee::all(['id', 'name']),
            'total_expenses_period' => $totalExpenses,
            'total_expenses_month' => $thisMonthExpenses,
            'filters' => $request->all(['filter', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'employee_id' => 'required|exists:employees,id',
            'description' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        Expense::create($validated);

        return redirect()->back();
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->back();
    }
}
