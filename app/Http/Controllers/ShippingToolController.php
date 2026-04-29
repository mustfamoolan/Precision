<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingToolController extends Controller
{
    /**
     * Display a listing of shipments.
     */
    public function index(Request $request)
    {
        $query = Shipment::withCount('sales');

        if ($request->filled('search')) {
            $query->where('container_number', 'like', '%' . $request->search . '%')
                  ->orWhere('vessel_name', 'like', '%' . $request->search . '%');
        }

        $shipments = $query->latest()->get();

        // Summary stats
        $summary = [
            'total_shipments' => $shipments->count(),
            'active_shipments' => $shipments->whereNotIn('status', ['Completed', 'Delivered'])->count(),
            'total_shipping_costs' => $shipments->sum('shipping_cost'),
            'total_tax_clearance' => $shipments->sum('import_tax') + $shipments->sum('clearance_fees'),
        ];

        return Inertia::render('Shipping', [
            'shipments' => $shipments,
            'summary' => $summary,
            'filters' => $request->all(['search'])
        ]);
    }

    /**
     * Store a newly created shipment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'container_number' => 'required|string|unique:shipments',
            'vessel_name' => 'nullable|string',
            'origin' => 'nullable|string',
            'destination' => 'nullable|string',
            'departure_date' => 'nullable|date',
            'arrival_date' => 'nullable|date',
            'status' => 'required|string|in:On Board,In Transit,Delivered,Completed',
            'shipping_cost' => 'nullable|numeric|min:0',
            'import_tax' => 'nullable|numeric|min:0',
            'clearance_fees' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $shipment = Shipment::create($validated);

        // Automatically link existing sales with this container number
        Sale::where('container_number', $shipment->container_number)
            ->update(['shipment_id' => $shipment->id]);

        return redirect()->back()->with('success', 'Shipment created successfully.');
    }

    /**
     * Update the specified shipment.
     */
    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'container_number' => 'required|string|unique:shipments,container_number,' . $shipment->id,
            'vessel_name' => 'nullable|string',
            'origin' => 'nullable|string',
            'destination' => 'nullable|string',
            'departure_date' => 'nullable|date',
            'arrival_date' => 'nullable|date',
            'status' => 'required|string|in:On Board,In Transit,Delivered,Completed',
            'shipping_cost' => 'nullable|numeric|min:0',
            'import_tax' => 'nullable|numeric|min:0',
            'clearance_fees' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $shipment->update($validated);

        // Update link for sales if container number changed
        Sale::where('container_number', $shipment->container_number)
            ->update(['shipment_id' => $shipment->id]);

        return redirect()->back()->with('success', 'Shipment updated successfully.');
    }

    /**
     * Remove the specified shipment.
     */
    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return redirect()->back()->with('success', 'Shipment deleted.');
    }
}
