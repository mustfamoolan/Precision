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

        $employee = Employee::create($validated);
        \App\Services\ActivityLogger::log('created', 'Added new employee: ' . $employee->name, $employee);

        return redirect()->back();
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $employee->update($validated);
        \App\Services\ActivityLogger::log('updated', 'Updated employee info: ' . $employee->name, $employee);

        return redirect()->back();
    }

    public function destroy(Employee $employee)
    {
        $name = $employee->name;
        $employee->delete();
        \App\Services\ActivityLogger::log('deleted', 'Deleted employee: ' . $name);

        return redirect()->back();
    }
}
