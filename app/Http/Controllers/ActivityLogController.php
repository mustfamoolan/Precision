<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhere('event', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($qu) use ($request) {
                      $qu->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        return Inertia::render('ActivityLog', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search'])
        ]);
    }
}
