<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Inventory', [
            'inventory' => Inventory::latest()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'selling_price' => 'nullable|numeric|min:0',
            'shop_quantity' => 'required|integer|min:0',
            'warehouse_quantity' => 'required|integer|min:0',
        ]);

        Inventory::create($validated);

        return redirect()->back()->with('success', 'Product added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'selling_price' => 'nullable|numeric|min:0',
            'shop_quantity' => 'sometimes|required|integer|min:0',
            'warehouse_quantity' => 'sometimes|required|integer|min:0',
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
            'from' => 'required|in:shop,warehouse',
            'to' => 'required|in:shop,warehouse',
        ]);

        $fromField = $validated['from'] . '_quantity';
        $toField = $validated['to'] . '_quantity';

        if ($inventory->$fromField < $validated['quantity']) {
            return redirect()->back()->withErrors(['quantity' => 'Insufficient stock in source location.']);
        }

        $inventory->$fromField -= $validated['quantity'];
        $inventory->$toField += $validated['quantity'];
        $inventory->save();

        return redirect()->back()->with('success', 'Stock transferred successfully.');
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
