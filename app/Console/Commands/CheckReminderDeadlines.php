<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckReminderDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for upcoming reminder deadlines and notify users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reminders = Reminder::where('status', '!=', 'done')->get();
        $today = Carbon::today();
        $users = User::all();

        if ($users->isEmpty()) {
            $this->error('No users found to notify.');
            return;
        }

        foreach ($reminders as $reminder) {
            $dueDate = Carbon::parse($reminder->date)->startOfDay();
            $daysUntil = $today->diffInDays($dueDate, false);

            $milestoneLabel = null;
            
            if ($daysUntil < 0) {
                $milestoneLabel = "OVERDUE by " . abs($daysUntil) . " days";
            } elseif ($daysUntil == 0) {
                $milestoneLabel = "DUE TODAY";
            } elseif ($daysUntil <= 3) {
                $milestoneLabel = "due in $daysUntil days";
            }

            if ($milestoneLabel) {
                foreach ($users as $user) {
                    $this->notifyIfNeeded($user, $reminder, $daysUntil, $milestoneLabel);
                }
            }
        }

        $this->info('Reminder check complete.');
    }

    protected function notifyIfNeeded($user, $reminder, $milestone, $label)
    {
        // Check if we've already notified for this specific reminder, milestone AND day
        $existing = $user->notifications()
            ->whereDate('created_at', Carbon::today())
            ->where('data->reminder_id', $reminder->id)
            ->where('data->milestone', $milestone)
            ->exists();

        if (!$existing) {
            $user->notify(new SystemNotification([
                'title' => "Reminder: {$reminder->item}",
                'message' => "This task is $label. Status: " . ucfirst($reminder->status),
                'type' => 'reminder',
                'icon' => 'warning',
                'link' => '/reminders',
                'reminder_id' => $reminder->id, // custom fields for deduplication
                'milestone' => $milestone
            ]));
            
            $this->info("Notified admin about Reminder #{$reminder->id} ($label)");
        }
    }
}
