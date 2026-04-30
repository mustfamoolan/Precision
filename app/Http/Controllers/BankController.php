<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\Cheque;
use App\Models\Expense;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    /**
     * Display the Bank and Cheque System page.
     */
    public function index()
    {
        $banks = Bank::all();
        $bank1 = $banks->firstWhere('name', 'Bank 1');
        $bank2 = $banks->firstWhere('name', 'Bank 2');
        $cash = $banks->firstWhere('name', 'Cash');

        $transactions = \App\Models\BankTransaction::with('bank')->latest('date')->latest('id')->take(50)->get();
        $cash_log = \App\Models\BankTransaction::whereHas('bank', function($query) {
            $query->where('name', 'Cash');
        })->latest('date')->latest('id')->take(50)->get();

        return Inertia::render('Banks', [
            'banks' => $banks,
            'bank1' => $bank1,
            'bank2' => $bank2,
            'cash' => $cash,
            'transactions' => $transactions,
            'cash_log' => $cash_log,
            'incoming_cheques' => Cheque::where('type', 'incoming')->latest('due_date')->get(),
            'outgoing_cheques' => Cheque::where('type', 'outgoing')->latest('due_date')->get(),
            'received_cheques' => Cheque::where('status', 'received')->latest('updated_at')->take(10)->get(),
            'employees' => \App\Models\Employee::all(['id', 'name']),
        ]);
    }

    /**
     * Create a new bank account (Optional, but included for flexibility).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
        ]);

        Bank::create($validated);
        return redirect()->back()->with('success', 'Bank account added.');
    }

    /**
     * Update bank information.
     */
    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
        ]);

        $bank->update($validated);
        return redirect()->back()->with('success', 'Bank information updated.');
    }

    /**
     * Record an expense directly from a bank.
     */
    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {
            $bank = Bank::lockForUpdate()->find($validated['bank_id']);
            
            if ($bank->balance < $validated['amount']) {
                return redirect()->back()->withErrors(['amount' => 'Insufficient bank balance.']);
            }

            // Create expense (Assume employee_id is 1 or optional for bank-direct expenses)
            // If the schema requires employee_id, we'll need to handle it. 
            // In Expense.php, it's 'employee_id' => 'required' in ExpenseController@store.
            // I'll check the Expense model/migration.
            
            Expense::create([
                'date' => $validated['date'],
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'bank_id' => $validated['bank_id'],
                'employee_id' => $validated['employee_id'],
            ]);

            $bank->decrement('balance', $validated['amount']);

            return redirect()->back()->with('success', 'Bank expense recorded.');
        });
    }

    /**
     * Delete a bank account.
     */
    public function destroy(Bank $bank)
    {
        $bank->delete();
        return redirect()->back()->with('success', 'Bank account removed.');
    }
}
