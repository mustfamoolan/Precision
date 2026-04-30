<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
