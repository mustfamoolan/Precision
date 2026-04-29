<?php

namespace App\Observers;

use App\Models\Expense;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        if ($expense->amount > 0 && $expense->bank_id) {
            $bank = $expense->bank;
            $bank->decrement('balance', $expense->amount);

            \App\Models\BankTransaction::create([
                'bank_id' => $expense->bank_id,
                'amount' => $expense->amount,
                'type' => 'withdrawal',
                'reference_type' => 'Expense',
                'reference_id' => $expense->id,
                'description' => "Expense: {$expense->description}",
                'date' => $expense->date,
            ]);
        }
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        $oldAmount = $expense->getOriginal('amount');
        $oldBankId = $expense->getOriginal('bank_id');

        // Reverse old transaction
        if ($oldAmount > 0 && $oldBankId) {
            $oldBank = \App\Models\Bank::find($oldBankId);
            if ($oldBank) {
                $oldBank->increment('balance', $oldAmount);
            }
            \App\Models\BankTransaction::where('reference_type', 'Expense')
                ->where('reference_id', $expense->id)
                ->delete();
        }

        // Apply new transaction
        if ($expense->amount > 0 && $expense->bank_id) {
            $bank = $expense->bank;
            $bank->decrement('balance', $expense->amount);

            \App\Models\BankTransaction::create([
                'bank_id' => $expense->bank_id,
                'amount' => $expense->amount,
                'type' => 'withdrawal',
                'reference_type' => 'Expense',
                'reference_id' => $expense->id,
                'description' => "Updated Expense: {$expense->description}",
                'date' => $expense->date,
            ]);
        }
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        if ($expense->amount > 0 && $expense->bank_id) {
            $bank = $expense->bank;
            if ($bank) {
                $bank->increment('balance', $expense->amount);
            }
            \App\Models\BankTransaction::where('reference_type', 'Expense')
                ->where('reference_id', $expense->id)
                ->delete();
        }
    }
}
