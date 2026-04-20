<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        // Expected Data keys: title, message, type (cheque, reminder, info), icon, link
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'type' => $this->data['type'] ?? 'info',
            'icon' => $this->data['icon'] ?? 'notifications',
            'link' => $this->data['link'] ?? null,
        ], $this->data);
    }
}
