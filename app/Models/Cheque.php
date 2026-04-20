<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    /** @use HasFactory<\Database\Factories\ChequeFactory> */
    use HasFactory;

    protected $fillable = ['cheque_number', 'party_name', 'amount', 'due_date', 'status', 'bank_id'];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
