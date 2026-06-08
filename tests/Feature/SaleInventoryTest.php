<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_sale_deducts_inventory()
    {
        $inventory = Inventory::create([
            'name' => 'Test Item',
            'sku' => 'TEST-SKU',
            'shop_quantity' => 10,
            'warehouse_quantity' => 20,
            'remote_quantity' => 30,
        ]);

        $sale = Sale::create([
            'date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-1001',
            'customer_name' => 'John Doe',
            'amount' => 100,
            'type' => 'local',
            'items_count' => 1,
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'name' => 'Test Item',
                    'quantity' => 3,
                    'rate' => 33.33,
                    'location' => 'shop',
                ]
            ],
            'status' => 'pending'
        ]);

        $inventory->refresh();
        $this->assertEquals(7, $inventory->shop_quantity);
        $this->assertEquals(20, $inventory->warehouse_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $inventory->id,
            'quantity' => 3,
            'type' => 'out',
            'from_location' => 'shop',
            'reference_type' => 'Sale',
            'reference_id' => $sale->id,
        ]);
    }

    public function test_updating_sale_adjusts_inventory()
    {
        $inventory = Inventory::create([
            'name' => 'Test Item',
            'sku' => 'TEST-SKU',
            'shop_quantity' => 10,
            'warehouse_quantity' => 20,
        ]);

        $sale = Sale::create([
            'date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-1001',
            'customer_name' => 'John Doe',
            'amount' => 100,
            'type' => 'local',
            'items_count' => 1,
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'name' => 'Test Item',
                    'quantity' => 3,
                    'location' => 'shop',
                ]
            ],
            'status' => 'pending'
        ]);

        $inventory->refresh();
        $this->assertEquals(7, $inventory->shop_quantity);

        // Update quantity to 5
        $sale->update([
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'name' => 'Test Item',
                    'quantity' => 5,
                    'location' => 'shop',
                ]
            ]
        ]);

        $inventory->refresh();
        $this->assertEquals(5, $inventory->shop_quantity);

        // Update location to warehouse
        $sale->update([
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'name' => 'Test Item',
                    'quantity' => 4,
                    'location' => 'warehouse',
                ]
            ]
        ]);

        $inventory->refresh();
        $this->assertEquals(10, $inventory->shop_quantity); // shop quantity reverted
        $this->assertEquals(16, $inventory->warehouse_quantity); // warehouse quantity decremented by 4
    }

    public function test_deleting_sale_reverts_inventory()
    {
        $inventory = Inventory::create([
            'name' => 'Test Item',
            'sku' => 'TEST-SKU',
            'shop_quantity' => 10,
        ]);

        $sale = Sale::create([
            'date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-1001',
            'customer_name' => 'John Doe',
            'amount' => 100,
            'type' => 'local',
            'items_count' => 1,
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'name' => 'Test Item',
                    'quantity' => 3,
                    'location' => 'shop',
                ]
            ],
            'status' => 'pending'
        ]);

        $inventory->refresh();
        $this->assertEquals(7, $inventory->shop_quantity);

        $sale->delete();

        $inventory->refresh();
        $this->assertEquals(10, $inventory->shop_quantity);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'Sale',
            'reference_id' => $sale->id,
        ]);
    }
}
