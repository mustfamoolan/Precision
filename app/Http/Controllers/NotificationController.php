<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');
        $query = Auth::user()->notifications();

        // Strictly show only financial/cheque notifications
        $query->where('data->type', 'financial');

        if ($tab === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();
        
        return Inertia::render('Notifications', [
            'notifications' => $notifications,
            'filters' => ['tab' => $tab]
        ]);
    }

    public function unread()
    {
        $user = Auth::user();

        if (!Cache::has('last_reminder_check')) {
            Artisan::call('app:check-reminders');
            Artisan::call('app:check-events');
            Cache::put('last_reminder_check', true, now()->addMinutes(5));
        }

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $user->unreadNotifications()->latest()->limit(5)->get(),
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return back();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }

    public function clearAll(Request $request)
    {
        $tab = $request->query('tab', 'all');
        $query = Auth::user()->notifications();

        if ($tab === 'unread') {
            $query->whereNull('read_at');
        } elseif (in_array($tab, ['reminder', 'financial', 'shipping'])) {
            $query->where('data->type', $tab);
        }

        $query->delete();
        
        return back();
    }

    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        
        return back();
    }
}
