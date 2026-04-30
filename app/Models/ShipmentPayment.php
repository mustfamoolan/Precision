<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentPayment extends Model
{
    protected $fillable = ['shipment_id', 'amount', 'payment_date', 'payment_method', 'note'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
