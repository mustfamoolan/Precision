<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\Brand;
use App\Models\Customer;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventory = Inventory::with('brand')->latest()->get();
        $brands = Brand::withCount('products')->get();
        
        $summary = [
            'total_items' => $inventory->count(),
            'total_valuation' => $inventory->sum('valuation'),
            'low_stock_items' => $inventory->filter(fn($item) => $item->total_quantity <= $item->low_stock_threshold)->count(),
        ];

        return Inertia::render('Inventory', [
            'inventory' => $inventory,
            'brands' => $brands,
            'customers' => Customer::all(['id', 'name']),
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
            'brand_id' => 'nullable|exists:brands,id',
            'category' => 'nullable|string|max:100',
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
            'brand_id' => 'nullable|exists:brands,id',
            'category' => 'nullable|string|max:100',
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
     * Store a newly created brand.
     */
    public function storeBrand(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        Brand::create($validated);

        return redirect()->back()->with('success', 'Brand added successfully.');
    }

    /**
     * Deduct stock for a customer.
     */
    public function deductForCustomer(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quantity' => 'required|integer|min:1',
            'location' => 'required|in:shop,warehouse,remote',
            'notes' => 'nullable|string',
        ]);

        $field = $validated['location'] . '_quantity';

        if ($inventory->$field < $validated['quantity']) {
            return redirect()->back()->withErrors(['quantity' => 'Insufficient stock in selected location.']);
        }

        $customer = Customer::find($validated['customer_id']);

        return DB::transaction(function () use ($inventory, $validated, $field, $customer) {
            $inventory->$field -= $validated['quantity'];
            $inventory->save();

            $this->logMovement(
                $inventory->id, 
                $validated['quantity'], 
                'out', 
                $validated['location'], 
                'customer', 
                ($validated['notes'] ?? 'Deduction for customer') . ": " . $customer->name
            );

            return redirect()->back()->with('success', 'Stock deducted successfully.');
        });
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
     * Adjust stock (Increase/Decrease)
     */
    public function adjust(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'location' => 'required|in:shop,warehouse,remote',
            'type' => 'required|in:in,out',
            'notes' => 'nullable|string',
        ]);

        $field = $validated['location'] . '_quantity';

        return DB::transaction(function () use ($inventory, $validated, $field) {
            if ($validated['type'] === 'in') {
                $inventory->$field += $validated['quantity'];
            } else {
                if ($inventory->$field < $validated['quantity']) {
                    return redirect()->back()->withErrors(['quantity' => 'Insufficient stock in selected location.']);
                }
                $inventory->$field -= $validated['quantity'];
            }
            $inventory->save();

            $this->logMovement(
                $inventory->id, 
                $validated['quantity'], 
                $validated['type'], 
                $validated['type'] === 'out' ? $validated['location'] : null, 
                $validated['type'] === 'in' ? $validated['location'] : null, 
                $validated['notes'] ?? 'Manual Adjustment'
            );

            return redirect()->back()->with('success', 'Stock adjusted successfully.');
        });
    }

    /**
     * Get history for a specific item.
     */
    public function history(Inventory $inventory)
    {
        return response()->json([
            'item' => $inventory,
            'movements' => StockMovement::where('inventory_id', $inventory->id)->latest()->get()
        ]);
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
