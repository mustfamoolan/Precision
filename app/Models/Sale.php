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

    protected static function booted()
    {
        static::created(function ($sale) {
            $sale->adjustInventory('deduct', 'new');
        });

        static::updated(function ($sale) {
            if ($sale->isDirty(['items', 'type', 'customer_name', 'invoice_number'])) {
                // Revert inventory using original values
                $originalType = $sale->getOriginal('type');
                $originalItems = $sale->getOriginal('items');
                $originalCustomer = $sale->getOriginal('customer_name');
                
                $originalSale = new static([
                    'type' => $originalType,
                    'items' => $originalItems,
                    'customer_name' => $originalCustomer,
                    'invoice_number' => $sale->getOriginal('invoice_number') ?? $sale->invoice_number,
                ]);
                $originalSale->id = $sale->id;
                
                $originalSale->adjustInventory('revert', 'edit');

                // Apply inventory using new values
                $sale->adjustInventory('deduct', 'edit');
            }
        });

        static::deleted(function ($sale) {
            $sale->adjustInventory('revert', 'delete');
        });
    }

    public function adjustInventory(string $action, string $reason = 'new'): void
    {
        $items = $this->items;
        if (empty($items) || !is_array($items)) {
            return;
        }

        if ($action === 'deduct') {
            foreach ($items as $item) {
                if (empty($item['inventory_id'])) {
                    continue;
                }

                $inventory = Inventory::find($item['inventory_id']);
                if ($inventory) {
                    $location = $item['location'] ?? ($this->type === 'local' ? 'shop' : 'warehouse');
                    $qtyField = $location . '_quantity';
                    $qty = intval($item['quantity'] ?? 0);
                    $inventory->decrement($qtyField, $qty);

                    StockMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $qty,
                        'type' => 'out',
                        'from_location' => $location,
                        'to_location' => 'customer',
                        'reference_type' => 'Sale',
                        'reference_id' => $this->id,
                        'notes' => ($reason === 'edit'
                            ? ($this->type === 'local' ? 'Local Sale (Edit)' : 'Export Sale (Edit)')
                            : ($this->type === 'local' ? 'Local Sale (New)' : 'Export Sale (New)'))
                            . ": {$this->invoice_number} for {$this->customer_name}"
                    ]);
                }
            }
        } elseif ($action === 'revert') {
            foreach ($items as $item) {
                if (empty($item['inventory_id'])) {
                    continue;
                }

                $inventory = Inventory::find($item['inventory_id']);
                if ($inventory) {
                    $location = $item['location'] ?? ($this->type === 'local' ? 'shop' : 'warehouse');
                    $qtyField = $location . '_quantity';
                    $qty = intval($item['quantity'] ?? 0);
                    $inventory->increment($qtyField, $qty);

                    StockMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $qty,
                        'type' => 'in',
                        'from_location' => 'customer',
                        'to_location' => $location,
                        'reference_type' => 'Sale',
                        'reference_id' => $this->id,
                        'notes' => ($reason === 'delete'
                            ? ($this->type === 'local' ? 'Returned due to Sale Deletion' : 'Returned due to Export Sale Deletion')
                            : ($this->type === 'local' ? 'Returned due to Sale Edit' : 'Returned due to Export Sale Edit'))
                            . ": {$this->invoice_number} for {$this->customer_name}"
                    ]);
                }
            }
        }
    }
}
