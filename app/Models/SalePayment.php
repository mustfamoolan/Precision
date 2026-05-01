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
                'description' => "Payment for Sale #{$payment->sale->invoice_number}",
                'date' => $payment->date,
            ]);
        });

        static::deleted(function ($payment) {
            // Reverse bank balance
            $payment->bank->decrement('balance', $payment->amount);

            // Remove transaction record
            BankTransaction::where('reference_type', 'SalePayment')
                ->where('reference_id', $payment->id)
                ->delete();
        });
    }
}
