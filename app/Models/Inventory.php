<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'name',
        'sku',
        'selling_price',
        'shop_quantity',
        'warehouse_quantity',
    ];

    /**
     * Total quantity across all locations.
     */
    protected $appends = ['total_quantity'];

    public function getTotalQuantityAttribute()
    {
        return $this->shop_quantity + $this->warehouse_quantity;
    }
}
