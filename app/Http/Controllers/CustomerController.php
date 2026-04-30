<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Customer::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $customer->update($validated);

        return redirect()->back();
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->back();
    }
}
