<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'name',
        'category',
        'sku',
        'cost_price',
        'selling_price',
        'shop_quantity',
        'warehouse_quantity',
        'remote_quantity',
        'low_stock_threshold',
    ];

    /**
     * Total quantity across all locations.
     */
    protected $appends = ['total_quantity', 'valuation'];

    public function getTotalQuantityAttribute()
    {
        return $this->shop_quantity + $this->warehouse_quantity + $this->remote_quantity;
    }

    public function getValuationAttribute()
    {
        return $this->total_quantity * ($this->cost_price ?? 0);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
