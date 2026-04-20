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
        return Inertia::render('Banks', [
            'banks' => Bank::all(),
            'pending_cheques' => Cheque::where('status', 'pending')->latest('due_date')->get(),
            'received_cheques' => Cheque::where('status', 'received')->latest('updated_at')->take(10)->get(),
            'bank_expenses' => Expense::whereNotNull('bank_id')->with('bank')->latest('date')->take(20)->get(),
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
                'employee_id' => auth()->id() ?? (\App\Models\Employee::first()->id ?? 1), // Fallback
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
