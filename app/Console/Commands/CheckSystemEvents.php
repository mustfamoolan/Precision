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

        // Check Cheques
        $cheques = Cheque::where('status', 'pending')->get();
        foreach ($cheques as $cheque) {
            if (!$cheque->due_date) continue;
            
            $dueDate = Carbon::parse($cheque->due_date)->startOfDay();
            $daysUntil = $today->diffInDays($dueDate, false);

            if ($daysUntil <= 3 && $daysUntil >= 0) {
                $label = $daysUntil == 0 ? "DUE TODAY" : "due in $daysUntil days";
                $this->notifyUsers($users, 'financial', $cheque->id, "Cheque Alert: {$cheque->party_name}", "Cheque #{$cheque->cheque_number} is $label. Amount: AED {$cheque->amount}.", 'payments', '/banks');
            } elseif ($daysUntil < 0) {
                $this->notifyUsers($users, 'financial', $cheque->id, "OVERDUE Cheque: {$cheque->party_name}", "Cheque #{$cheque->cheque_number} is overdue by " . abs($daysUntil) . " days. Amount: AED {$cheque->amount}.", 'payments', '/banks');
            }
        }

        // Check Shipments
        $shipments = Shipment::whereNotIn('status', ['Delivered', 'Completed'])->get();
        foreach ($shipments as $shipment) {
            if (!$shipment->arrival_date) continue;
            
            $etaDate = Carbon::parse($shipment->arrival_date)->startOfDay();
            $daysUntil = $today->diffInDays($etaDate, false);

            if ($daysUntil <= 5 && $daysUntil >= 0) {
                $label = $daysUntil == 0 ? "ARRIVING TODAY" : "arriving in $daysUntil days";
                $this->notifyUsers($users, 'shipping', $shipment->id, "Shipment ETA: {$shipment->container_number}", "Container {$shipment->container_number} is $label.", 'local_shipping', "/shipping/{$shipment->id}");
            }
        }

        $this->info('System events checked.');
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
