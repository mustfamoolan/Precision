<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cheque;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\Carbon;

class CheckSystemEvents extends Command
{
    protected $signature = 'app:check-events';
    protected $description = 'Check for upcoming financial and shipping events and notify users';

    public function handle()
    {
        $today = Carbon::today();
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        // Check Cheques ONLY
        $cheques = Cheque::where('status', 'pending')->get();
        foreach ($cheques as $cheque) {
            if (!$cheque->due_date) continue;
            
            $dueDate = Carbon::parse($cheque->due_date)->startOfDay();
            $daysUntil = $today->diffInDays($dueDate, false);

            // Notify if due within 5 days or overdue
            if ($daysUntil <= 5 && $daysUntil >= 0) {
                $label = $daysUntil == 0 ? "DUE TODAY" : "due in $daysUntil days";
                $this->notifyUsers($users, 'financial', $cheque->id, "Cheque Alert: {$cheque->party_name}", "Cheque #{$cheque->cheque_number} is $label. Amount: AED {$cheque->amount}.", 'payments', '/banks');
            } elseif ($daysUntil < 0) {
                $this->notifyUsers($users, 'financial', $cheque->id, "OVERDUE Cheque: {$cheque->party_name}", "Cheque #{$cheque->cheque_number} is overdue by " . abs($daysUntil) . " days. Amount: AED {$cheque->amount}.", 'payments', '/banks');
            }
        }

        $this->info('Cheque events checked.');
    }

    protected function notifyUsers($users, $type, $resourceId, $title, $message, $icon, $link)
    {
        foreach ($users as $user) {
            // Deduplication: Avoid duplicate notifications for the same resource on the same day
            $existing = $user->notifications()
                ->whereDate('created_at', Carbon::today())
                ->where('data->type', $type)
                ->where('data->resource_id', $resourceId)
                ->exists();

            if (!$existing) {
                $user->notify(new SystemNotification([
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'icon' => $icon,
                    'link' => $link,
                    'resource_id' => $resourceId
                ]));
            }
        }
    }
}
