<?php

namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        if ($sale->paid_amount > 0 && $sale->bank_id) {
            $bank = $sale->bank;
            $bank->increment('balance', $sale->paid_amount);

            \App\Models\BankTransaction::create([
                'bank_id' => $sale->bank_id,
                'amount' => $sale->paid_amount,
                'type' => 'deposit',
                'reference_type' => 'Sale',
                'reference_id' => $sale->id,
                'description' => "Sale #{$sale->invoice_number} from {$sale->customer_name}",
                'date' => $sale->date,
            ]);
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        $oldPaidAmount = $sale->getOriginal('paid_amount');
        $oldBankId = $sale->getOriginal('bank_id');

        // Reverse old transaction if exists
        if ($oldPaidAmount > 0 && $oldBankId) {
            $oldBank = \App\Models\Bank::find($oldBankId);
            if ($oldBank) {
                $oldBank->decrement('balance', $oldPaidAmount);
            }
            \App\Models\BankTransaction::where('reference_type', 'Sale')
                ->where('reference_id', $sale->id)
                ->delete();
        }

        // Apply new transaction
        if ($sale->paid_amount > 0 && $sale->bank_id) {
            $bank = $sale->bank;
            $bank->increment('balance', $sale->paid_amount);

            \App\Models\BankTransaction::create([
                'bank_id' => $sale->bank_id,
                'amount' => $sale->paid_amount,
                'type' => 'deposit',
                'reference_type' => 'Sale',
                'reference_id' => $sale->id,
                'description' => "Updated Sale #{$sale->invoice_number} from {$sale->customer_name}",
                'date' => $sale->date,
            ]);
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        if ($sale->paid_amount > 0 && $sale->bank_id) {
            $bank = $sale->bank;
            if ($bank) {
                $bank->decrement('balance', $sale->paid_amount);
            }
            \App\Models\BankTransaction::where('reference_type', 'Sale')
                ->where('reference_id', $sale->id)
                ->delete();
        }
    }
}
