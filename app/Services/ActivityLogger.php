<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log a system activity
     *
     * @param string $event The event type (created, updated, deleted, etc.)
     * @param string $description Human readable description
     * @param mixed $subject The model being acted upon (optional)
     * @param array $properties Additional metadata (optional)
     * @return ActivityLog
     */
    public static function log($event, $description, $subject = null, array $properties = [])
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }
}
