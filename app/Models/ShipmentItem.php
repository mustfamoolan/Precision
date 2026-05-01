<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    protected $fillable = ['shipment_id', 'product_name', 'quantity', 'type', 'cost', 'currency'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
