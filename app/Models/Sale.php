<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'date', 
        'invoice_number', 
        'customer_name', 
        'amount', 
        'type', 
        'items_count', 
        'paid_amount', 
        'due_amount', 
        'status', 
        'container_number', 
        'shipping_status',
        'bank_id',
        'shipment_id',
        'items',
        'customer_address',
        'has_tax',
        'currency',
        'trn',
        'notes'
    ];

    protected $casts = [
        'items' => 'array',
        'has_tax' => 'boolean'
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }
}
