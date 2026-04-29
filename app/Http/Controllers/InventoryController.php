<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\StockMovement;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventory = Inventory::latest()->get();
        
        $summary = [
            'total_items' => $inventory->count(),
            'total_valuation' => $inventory->sum('valuation'),
            'low_stock_items' => $inventory->filter(fn($item) => $item->total_quantity <= $item->low_stock_threshold)->count(),
        ];

        return Inertia::render('Inventory', [
            'inventory' => $inventory,
            'summary' => $summary,
            'movements' => StockMovement::with('inventory')->latest()->limit(20)->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'shop_quantity' => 'required|integer|min:0',
            'warehouse_quantity' => 'required|integer|min:0',
            'remote_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $item = Inventory::create($validated);

            // Log initial stock as movements
            if ($item->shop_quantity > 0) {
                $this->logMovement($item->id, $item->shop_quantity, 'in', null, 'shop', 'Initial Stock');
            }
            if ($item->warehouse_quantity > 0) {
                $this->logMovement($item->id, $item->warehouse_quantity, 'in', null, 'warehouse', 'Initial Stock');
            }
            if ($item->remote_quantity > 0) {
                $this->logMovement($item->id, $item->remote_quantity, 'in', null, 'remote', 'Initial Stock');
            }

            return redirect()->back()->with('success', 'Product added successfully.');
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'shop_quantity' => 'sometimes|required|integer|min:0',
            'warehouse_quantity' => 'sometimes|required|integer|min:0',
            'remote_quantity' => 'sometimes|required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $inventory->update($validated);

        return redirect()->back()->with('success', 'Inventory updated.');
    }

    /**
     * Transfer stock between locations.
     */
    public function transfer(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'from' => 'required|in:shop,warehouse,remote',
            'to' => 'required|in:shop,warehouse,remote',
            'notes' => 'nullable|string',
        ]);

        if ($validated['from'] === $validated['to']) {
            return redirect()->back()->withErrors(['to' => 'Source and destination must be different.']);
        }

        $fromField = $validated['from'] . '_quantity';
        $toField = $validated['to'] . '_quantity';

        if ($inventory->$fromField < $validated['quantity']) {
            return redirect()->back()->withErrors(['quantity' => 'Insufficient stock in source location.']);
        }

        return DB::transaction(function () use ($inventory, $validated, $fromField, $toField) {
            $inventory->$fromField -= $validated['quantity'];
            $inventory->$toField += $validated['quantity'];
            $inventory->save();

            $this->logMovement(
                $inventory->id, 
                $validated['quantity'], 
                'transfer', 
                $validated['from'], 
                $validated['to'], 
                $validated['notes'] ?? 'Manual Transfer'
            );

            return redirect()->back()->with('success', 'Stock transferred successfully.');
        });
    }

    /**
     * Helper to log stock movements.
     */
    private function logMovement($inventoryId, $quantity, $type, $from, $to, $notes = null)
    {
        StockMovement::create([
            'inventory_id' => $inventoryId,
            'quantity' => $quantity,
            'type' => $type,
            'from_location' => $from,
            'to_location' => $to,
            'notes' => $notes,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->back()->with('success', 'Product removed.');
    }
}
