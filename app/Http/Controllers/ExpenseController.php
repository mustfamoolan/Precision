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
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
        } else {
            // Default to current month (resets on the 1st of every month)
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $query->whereBetween('date', [$start, $end]);
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
            'banks' => \App\Models\Bank::all(['id', 'name']),
            'summary' => [
                'total' => $totalExpenses,
                'office' => $officeExpenses,
                'shipping' => $shippingExpenses,
                'employee' => $employeeExpenses,
                'this_month_count' => $thisMonthCount,
            ],
            'filters' => array_merge($request->all(['filter', 'search', 'category']), [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ]),
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

        if ($validated['expense_number'] && !str_starts_with($validated['expense_number'], 'EXP-')) {
            $validated['expense_number'] = 'EXP-' . $validated['expense_number'];
        }

        Expense::create($validated);

        return redirect()->back();
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->back();
    }
}
