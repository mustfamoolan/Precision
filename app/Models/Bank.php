<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    /** @use HasFactory<\Database\Factories\BankFactory> */
    use HasFactory;

    protected $fillable = ['name', 'balance'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }
}
