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
    public function index(Request $request)
    {
        $banks = Bank::all();
        
        $txQuery = BankTransaction::with('bank')->latest('date')->latest('id');
        
        if ($request->bank && $request->bank !== 'all') {
            $txQuery->whereHas('bank', function($q) use ($request) {
                $q->where('name', $request->bank);
            });
        }
        
        if ($request->date_from) {
            $txQuery->where('date', '>=', $request->date_from);
        }
        
        if ($request->date_to) {
            $txQuery->where('date', '<=', $request->date_to);
        }

        $transactions = $txQuery->paginate(15, ['*'], 'tx_page')->withQueryString();
        
        $cash_log = BankTransaction::whereHas('bank', function($query) {
            $query->where('name', 'like', '%Cash%');
        })->latest('date')->latest('id')->paginate(15, ['*'], 'cash_page')->withQueryString();

        return Inertia::render('Banks', [
            'banks' => $banks,
            'transactions' => $transactions,
            'cash_log' => $cash_log,
            'incoming_cheques' => Cheque::with('bank')->where('type', 'incoming')->latest('due_date')->paginate(15, ['*'], 'in_cheque_page')->withQueryString(),
            'outgoing_cheques' => Cheque::with('bank')->where('type', 'outgoing')->latest('due_date')->paginate(15, ['*'], 'out_cheque_page')->withQueryString(),
            'employees' => \App\Models\Employee::all(['id', 'name']),
            'filters' => $request->only(['bank', 'date_from', 'date_to']),
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

            \App\Services\ActivityLogger::log('created', 'Created bank account: ' . $bank->name . ' with balance ' . $validated['balance'], $bank);

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

            \App\Services\ActivityLogger::log('updated', 'Adjusted ' . $bank->name . ' balance: ' . $validated['type'] . ' of ' . $validated['amount'], $bank);

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
        \App\Services\ActivityLogger::log('updated', 'Updated bank information for: ' . $bank->name, $bank);
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

            $expense = Expense::create([
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

            \App\Services\ActivityLogger::log('created', 'Recorded bank expense: ' . $validated['description'] . ' (' . $validated['amount'] . ') from ' . $bank->name, $expense);

            return redirect()->back()->with('success', 'Bank expense recorded.');
        });
    }

    /**
     * Transfer funds between bank accounts/cash.
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_bank_id' => 'required|exists:banks,id',
            'to_bank_id' => 'required|exists:banks,id|different:from_bank_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {
            $fromBank = Bank::lockForUpdate()->find($validated['from_bank_id']);
            $toBank = Bank::lockForUpdate()->find($validated['to_bank_id']);

            if ($fromBank->balance < $validated['amount']) {
                return redirect()->back()->withErrors(['amount' => 'Insufficient funds in ' . $fromBank->name]);
            }

            $description = $validated['description'] ?? "Transfer from {$fromBank->name} to {$toBank->name}";

            // 1. Create withdrawal from source bank
            BankTransaction::create([
                'bank_id' => $fromBank->id,
                'amount' => $validated['amount'],
                'type' => 'withdrawal',
                'reference_type' => 'Transfer Out',
                'description' => $description,
                'date' => $validated['date'],
            ]);

            // 2. Create deposit to destination bank
            BankTransaction::create([
                'bank_id' => $toBank->id,
                'amount' => $validated['amount'],
                'type' => 'deposit',
                'reference_type' => 'Transfer In',
                'description' => $description,
                'date' => $validated['date'],
            ]);

            // 3. Update Balances
            $fromBank->decrement('balance', $validated['amount']);
            $toBank->increment('balance', $validated['amount']);

            // 4. Log Activity
            \App\Services\ActivityLogger::log(
                'updated',
                "Transferred " . number_format($validated['amount'], 2) . " AED from {$fromBank->name} to {$toBank->name}",
                $fromBank
            );

            return redirect()->back()->with('success', 'Funds transferred successfully.');
        });
    }

    /**
     * Get history for a specific bank.
     */
    public function history(Request $request, Bank $bank)
    {
        $query = BankTransaction::where('bank_id', $bank->id)->latest('date')->latest('id');

        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        return response()->json([
            'bank' => $bank,
            'transactions' => $query->get()
        ]);
    }

    /**
     * Delete a bank account.
     */
    public function destroy(Bank $bank)
    {
        $name = $bank->name;
        $bank->delete();
        \App\Services\ActivityLogger::log('deleted', 'Deleted bank account: ' . $name);
        return redirect()->back()->with('success', 'Bank account removed.');
    }
}
