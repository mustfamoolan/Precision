<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        
        return Inertia::render('Notifications', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Get unread notifications and count for polling.
     */
    public function unread()
    {
        $user = Auth::user();

        // Passive background trigger for alerts, throttled to once every 5 mins
        if (!Cache::has('last_reminder_check')) {
            Artisan::call('app:check-reminders');
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
}
