<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShippingToolController extends Controller
{
    /**
     * Goal: Accept inputs and return calculated shipping costs.
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:0',
            'rate_per_kg' => 'required|numeric|min:0',
            'fixed_charge' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $weight = $validated['weight'];
        $rate = $validated['rate_per_kg'];
        $fixed = $validated['fixed_charge'] ?? 0;
        $discount = $validated['discount'] ?? 0;

        $total = ($weight * $rate) + $fixed - $discount;

        return response()->json([
            'inputs' => $validated,
            'calculation' => [
                'subtotal' => $weight * $rate,
                'fixed_charge' => $fixed,
                'discount' => $discount,
                'total' => max(0, $total),
            ]
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
