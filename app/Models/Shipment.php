<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'container_number',
        'vessel_name',
        'origin',
        'destination',
        'departure_date',
        'arrival_date',
        'status',
        'shipping_cost',
        'import_tax',
        'clearance_fees',
        'other_costs',
        'notes',
        'supplier_name',
        'invoice_amount',
        'paid_amount',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function payments()
    {
        return $this->hasMany(ShipmentPayment::class);
    }
}
