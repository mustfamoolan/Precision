<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\Cheque;
use App\Models\Expense;
use App\Models\BankTransaction;
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
        
        $transactions = BankTransaction::with('bank')->latest('date')->latest('id')->take(500)->get();
        $cash_log = BankTransaction::whereHas('bank', function($query) {
            $query->where('name', 'like', '%Cash%');
        })->latest('date')->latest('id')->take(500)->get();

        return Inertia::render('Banks', [
            'banks' => $banks,
            'transactions' => $transactions,
            'cash_log' => $cash_log,
            'incoming_cheques' => Cheque::with('bank')->where('type', 'incoming')->latest('due_date')->get(),
            'outgoing_cheques' => Cheque::with('bank')->where('type', 'outgoing')->latest('due_date')->get(),
            'employees' => \App\Models\Employee::all(['id', 'name']),
        ]);
    }

    /**
     * Create a new bank account with opening balance.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($validated) {
            $bank = Bank::create($validated);

            if ($validated['balance'] != 0) {
                BankTransaction::create([
                    'bank_id' => $bank->id,
                    'amount' => abs($validated['balance']),
                    'type' => $validated['balance'] > 0 ? 'deposit' : 'withdrawal',
                    'reference_type' => 'Opening Balance',
                    'description' => 'Initial balance for ' . $bank->name,
                    'date' => now(),
                ]);
            }

            return redirect()->back()->with('success', 'Bank account added with opening balance.');
        });
    }

    /**
     * Manual adjustment of bank/cash balance.
     */
    public function adjustBalance(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:deposit,withdrawal',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {
            $bank = Bank::lockForUpdate()->find($validated['bank_id']);

            if ($validated['type'] === 'withdrawal' && $bank->balance < $validated['amount']) {
                return redirect()->back()->withErrors(['amount' => 'Insufficient funds in ' . $bank->name]);
            }

            // Create Transaction Record
            BankTransaction::create([
                'bank_id' => $bank->id,
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'reference_type' => 'Manual Adjustment',
                'description' => $validated['description'],
                'date' => $validated['date'],
            ]);

            // Update Bank Balance
            if ($validated['type'] === 'deposit') {
                $bank->increment('balance', $validated['amount']);
            } else {
                $bank->decrement('balance', $validated['amount']);
            }

            return redirect()->back()->with('success', 'Balance adjusted successfully.');
        });
    }

    /**
     * Update bank information.
     */
    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric',
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

            Expense::create([
                'date' => $validated['date'],
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'bank_id' => $validated['bank_id'],
                'employee_id' => $validated['employee_id'],
            ]);

            // Log Transaction
            BankTransaction::create([
                'bank_id' => $bank->id,
                'amount' => $validated['amount'],
                'type' => 'withdrawal',
                'reference_type' => 'Expense',
                'description' => $validated['description'],
                'date' => $validated['date'],
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
