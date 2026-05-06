<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentPayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingToolController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::withCount('sales');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('container_number', 'like', '%' . $request->search . '%')
                  ->orWhere('vessel_name', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $shipments = $query->latest()->paginate(15)->through(function ($s) {
            $totalCosts = $s->shipping_cost + $s->import_tax + $s->clearance_fees + $s->other_costs;
            $balance    = max(0, $s->invoice_amount - $s->paid_amount);
            $payStatus  = $s->invoice_amount <= 0 ? 'N/A'
                : ($balance <= 0 ? 'Fully Paid' : ($s->paid_amount > 0 ? 'Partially Paid' : 'Unpaid'));

            return array_merge($s->toArray(), [
                'total_costs'    => $totalCosts,
                'balance_due'    => $balance,
                'payment_status' => $payStatus,
            ]);
        })->withQueryString();

        // Summary Data (Global)
        $summary = [
            'total_shipments'       => Shipment::count(),
            'in_transit'            => Shipment::whereIn('status', ['On Board', 'In Transit'])->count(),
            'arrived'               => Shipment::whereIn('status', ['Delivered', 'Completed'])->count(),
            'total_balance_due'     => Shipment::all()->sum(fn($s) => max(0, $s->invoice_amount - $s->paid_amount)),
            'total_shipping_costs'  => Shipment::sum('shipping_cost'),
            'total_tax_clearance'   => Shipment::sum('import_tax') + Shipment::sum('clearance_fees'),
        ];

        return Inertia::render('Shipping/Index', [
            'shipments' => $shipments,
            'summary'   => $summary,
            'filters'   => $request->all(['search', 'status']),
        ]);
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['items', 'payments', 'sales']);
        
        $totalCosts = $shipment->shipping_cost + $shipment->import_tax + $shipment->clearance_fees + $shipment->other_costs;
        $balance    = max(0, $shipment->invoice_amount - $shipment->paid_amount);
        $payStatus  = $shipment->invoice_amount <= 0 ? 'N/A'
            : ($balance <= 0 ? 'Fully Paid' : ($shipment->paid_amount > 0 ? 'Partially Paid' : 'Unpaid'));

        $data = array_merge($shipment->toArray(), [
            'total_costs'    => $totalCosts,
            'balance_due'    => $balance,
            'payment_status' => $payStatus,
        ]);

        return Inertia::render('Shipping/Show', [
            'shipment' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'container_number' => 'required|string|unique:shipments',
            'vessel_name'      => 'nullable|string',
            'origin'           => 'nullable|string',
            'destination'      => 'nullable|string',
            'departure_date'   => 'nullable|date',
            'arrival_date'     => 'nullable|date',
            'status'           => 'required|string|in:On Board,In Transit,Delivered,Completed',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'import_tax'       => 'nullable|numeric|min:0',
            'clearance_fees'   => 'nullable|numeric|min:0',
            'other_costs'      => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
            'supplier_name'    => 'nullable|string',
            'invoice_amount'   => 'nullable|numeric|min:0',
            'paid_amount'      => 'nullable|numeric|min:0',
        ]);

        $shipment = Shipment::create($validated);

        Sale::where('container_number', $shipment->container_number)
            ->update(['shipment_id' => $shipment->id]);

        return redirect()->route('shipping.show', $shipment->id)->with('success', 'Shipment created successfully.');
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'container_number' => 'required|string|unique:shipments,container_number,' . $shipment->id,
            'vessel_name'      => 'nullable|string',
            'origin'           => 'nullable|string',
            'destination'      => 'nullable|string',
            'departure_date'   => 'nullable|date',
            'arrival_date'     => 'nullable|date',
            'status'           => 'required|string|in:On Board,In Transit,Delivered,Completed',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'import_tax'       => 'nullable|numeric|min:0',
            'clearance_fees'   => 'nullable|numeric|min:0',
            'other_costs'      => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
            'supplier_name'    => 'nullable|string',
            'invoice_amount'   => 'nullable|numeric|min:0',
            'paid_amount'      => 'nullable|numeric|min:0',
        ]);

        $shipment->update($validated);

        Sale::where('container_number', $shipment->container_number)
            ->update(['shipment_id' => $shipment->id]);

        return redirect()->back()->with('success', 'Shipment updated successfully.');
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return redirect()->route('shipping')->with('success', 'Shipment deleted.');
    }

    public function storeItem(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'product_name' => 'required|string',
            'quantity'     => 'required|integer|min:1',
            'type'         => 'nullable|string',
            'cost'         => 'nullable|numeric|min:0',
            'currency'     => 'nullable|string|max:10',
        ]);

        $shipment->items()->create($validated);

        return redirect()->back()->with('success', 'Item added to packing list.');
    }

    public function deleteItem(ShipmentItem $item)
    {
        $item->delete();
        return redirect()->back()->with('success', 'Item removed.');
    }

    public function storePayment(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'nullable|string',
            'note'           => 'nullable|string',
        ]);

        $shipment->payments()->create($validated);
        
        // Update paid_amount in shipment
        $shipment->increment('paid_amount', $validated['amount']);

        return redirect()->back()->with('success', 'Payment recorded.');
    }

    public function deletePayment(ShipmentPayment $payment)
    {
        $shipment = $payment->shipment;
        $shipment->decrement('paid_amount', $payment->amount);
        $payment->delete();
        return redirect()->back()->with('success', 'Payment removed.');
    }
}
