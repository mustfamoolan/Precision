<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reminder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

class ReminderController extends Controller
{
    public function index()
    {
        return Inertia::render('Reminders', [
            'reminders' => Reminder::latest('date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'item' => 'required|string|max:255',
            'quantity' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,done',
        ]);

        Reminder::create($validated);
        Artisan::call('app:check-reminders');

        return redirect()->back()->with('success', 'Reminder created successfully.');
    }

    public function update(Request $request, Reminder $reminder)
    {
        $validated = $request->validate([
            'date' => 'sometimes|required|date',
            'item' => 'sometimes|required|string|max:255',
            'quantity' => 'nullable|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,done',
        ]);

        $reminder->update($validated);
        Artisan::call('app:check-reminders');

        return redirect()->back()->with('success', 'Reminder updated successfully.');
    }

    public function destroy(Reminder $reminder)
    {
        $reminder->delete();

        return redirect()->back()->with('success', 'Reminder deleted successfully.');
    }
}
