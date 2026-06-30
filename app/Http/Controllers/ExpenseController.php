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

        if ($request->filled('bank_id') && $request->bank_id !== 'all') {
            $query->where('bank_id', $request->bank_id);
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

        $expenses = $query->latest('date')->latest('id')->paginate(15)->withQueryString();
        
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
            'filters' => array_merge($request->all(['filter', 'search', 'category', 'bank_id']), [
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
            'is_cheque' => 'nullable|boolean',
            'cheque_number' => 'nullable|required_if:is_cheque,true|string',
            'cheque_due_date' => 'nullable|required_if:is_cheque,true|date',
            'cheque_sender_name' => 'nullable|required_if:is_cheque,true|string',
            'cheque_receiver_name' => 'nullable|required_if:is_cheque,true|string',
        ]);

        if (empty($validated['expense_number'])) {
            $lastExpense = Expense::orderBy('id', 'desc')->first();
            $nextId = $lastExpense ? ($lastExpense->id + 1) : 1;
            $validated['expense_number'] = $nextId;
        }

        if (!empty($request->is_cheque)) {
            $bankIdForCheque = $validated['bank_id'];
            $validated['bank_id'] = null; // Prevent ExpenseObserver from deducting balance immediately
            $validated['payment_method'] = 'Cheque';
            
            $expense = Expense::create($validated);
            
            \App\Models\Cheque::create([
                'cheque_number' => $request->cheque_number,
                'sender_name' => $request->cheque_sender_name,
                'receiver_name' => $request->cheque_receiver_name,
                'party_name' => $request->cheque_receiver_name,
                'amount' => $expense->amount,
                'due_date' => $request->cheque_due_date,
                'type' => 'outgoing',
                'bank_id' => $bankIdForCheque,
                'status' => 'pending',
            ]);
        } else {
            $expense = Expense::create($validated);
        }
        
        \App\Services\ActivityLogger::log('created', 'Recorded new expense: ' . $expense->description . ' (' . $expense->amount . ')', $expense);

        return redirect()->back();
    }

    public function destroy(Expense $expense)
    {
        $desc = $expense->description;
        $expense->delete();
        \App\Services\ActivityLogger::log('deleted', 'Deleted expense: ' . $desc);
        return redirect()->back();
    }
}
