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
        $query = Expense::with(['employee', 'bank']);

        // Filtering Logic
        if ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->get('filter') === 'week') {
            $query->where('date', '>=', Carbon::now()->startOfWeek());
        } elseif ($request->get('filter') === 'month') {
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
        }

        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('expense_number', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier_person', 'like', '%' . $request->search . '%')
                  ->orWhereHas('employee', function($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $expenses = $query->latest('date')->get();
        
        // Summary Data for KPIs (based on Image 1)
        $totalExpenses = $query->sum('amount');
        $officeExpenses = (clone $query)->where('category', 'Office')->sum('amount');
        $shippingExpenses = (clone $query)->where('category', 'Shipping')->sum('amount');
        $employeeExpenses = (clone $query)->where('category', 'Salary')->sum('amount');
        $thisMonthCount = Expense::whereMonth('date', Carbon::now()->month)
                                ->whereYear('date', Carbon::now()->year)
                                ->count();

        return Inertia::render('Expenses', [
            'expenses' => $expenses,
            'employees' => Employee::all(['id', 'name']),
            'summary' => [
                'total' => $totalExpenses,
                'office' => $officeExpenses,
                'shipping' => $shippingExpenses,
                'employee' => $employeeExpenses,
                'this_month_count' => $thisMonthCount,
            ],
            'filters' => $request->all(['filter', 'search', 'start_date', 'end_date', 'category']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'employee_id' => 'required|exists:employees,id',
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'expense_number' => 'nullable|string',
            'category' => 'required|string',
            'supplier_person' => 'nullable|string',
            'payment_method' => 'required|string',
            'status' => 'required|string',
            'bank_id' => 'nullable|exists:banks,id',
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
