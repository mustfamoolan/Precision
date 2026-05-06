<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reminder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $query = Reminder::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('item', 'like', '%' . $request->search . '%')
                  ->orWhere('notes', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reminders = $query->latest('date')->latest('id')->paginate(15)->withQueryString();

        $kpi = [
            'pending' => Reminder::where('status', 'pending')->count(),
            'in_progress' => Reminder::where('status', 'in_progress')->count(),
            'done' => Reminder::where('status', 'done')->count(),
        ];

        return Inertia::render('Reminders', [
            'reminders' => $reminders,
            'kpi' => $kpi,
            'filters' => $request->all(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'item' => 'required|string|max:255',
            'quantity' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
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
            'unit' => 'nullable|string|max:255',
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
