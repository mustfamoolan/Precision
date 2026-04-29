<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_id',
        'amount',
        'type',
        'reference_type',
        'reference_id',
        'description',
        'date'
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
