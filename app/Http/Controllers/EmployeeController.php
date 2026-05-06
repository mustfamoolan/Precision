<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        return Inertia::render('Employees', [
            'employees' => Employee::latest()->paginate(15, ['*'], 'emp_page')->withQueryString(),
            'customers' => \App\Models\Customer::latest()->paginate(15, ['*'], 'cust_page')->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Employee::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $employee->update($validated);

        return redirect()->back();
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->back();
    }
}
