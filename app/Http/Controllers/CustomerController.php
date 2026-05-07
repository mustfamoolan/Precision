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
            'address' => 'nullable|string|max:500',
        ]);

        $customer = Customer::create($validated);
        \App\Services\ActivityLogger::log('created', 'Added new customer: ' . $customer->name, $customer);

        return redirect()->back();
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($validated);
        \App\Services\ActivityLogger::log('updated', 'Updated customer info: ' . $customer->name, $customer);

        return redirect()->back();
    }

    public function destroy(Customer $customer)
    {
        $name = $customer->name;
        $customer->delete();
        \App\Services\ActivityLogger::log('deleted', 'Deleted customer: ' . $name);

        return redirect()->back();
    }
}
