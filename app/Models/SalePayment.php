<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = ['sale_id', 'bank_id', 'amount', 'date', 'note'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            // Update bank balance
            $payment->bank->increment('balance', $payment->amount);

            // Create bank transaction record
            BankTransaction::create([
                'bank_id' => $payment->bank_id,
                'amount' => $payment->amount,
                'type' => 'deposit',
                'reference_type' => 'SalePayment',
                'reference_id' => $payment->id,
                'description' => "Payment for Sale #{$payment->sale->invoice_number}" . ($payment->note ? " ({$payment->note})" : ""),
                'date' => $payment->date,
            ]);

            \App\Services\ActivityLogger::log('created', "Recorded payment of {$payment->amount} to {$payment->bank->name} for Sale #{$payment->sale->invoice_number}", $payment);
        });

        static::updated(function ($payment) {
            if ($payment->isDirty(['amount', 'bank_id', 'date', 'note'])) {
                // Reverse old bank balance if bank or amount changed
                if ($payment->isDirty(['amount', 'bank_id'])) {
                    $oldBankId = $payment->getOriginal('bank_id');
                    $oldAmount = $payment->getOriginal('amount');
                    Bank::find($oldBankId)->decrement('balance', $oldAmount);

                    // Add new bank balance
                    $payment->bank->increment('balance', $payment->amount);
                }

                // Update transaction record
                BankTransaction::where('reference_type', 'SalePayment')
                    ->where('reference_id', $payment->id)
                    ->update([
                        'bank_id' => $payment->bank_id,
                        'amount' => $payment->amount,
                        'date' => $payment->date,
                        'description' => "Updated Payment for Sale #{$payment->sale->invoice_number}" . ($payment->note ? " ({$payment->note})" : ""),
                    ]);

                \App\Services\ActivityLogger::log('updated', "Updated payment details: {$payment->amount} to {$payment->bank->name} for Sale #{$payment->sale->invoice_number}", $payment);
            }
        });

        static::deleted(function ($payment) {
            // Reverse bank balance
            $payment->bank->decrement('balance', $payment->amount);

            // Remove transaction record
            BankTransaction::where('reference_type', 'SalePayment')
                ->where('reference_id', $payment->id)
                ->delete();

            \App\Services\ActivityLogger::log('deleted', "Deleted payment of {$payment->amount} from {$payment->bank->name} for Sale #{$payment->sale->invoice_number}", $payment);
        });
    }
}
