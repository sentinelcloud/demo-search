<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnalyticsMilestoneNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $milestone,
        public int $value,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'milestone' => $this->milestone,
            'value' => $this->value,
            'message' => "Analytics milestone reached: {$this->milestone} = {$this->value}",
            'achieved_at' => now()->toIso8601String(),
        ];
    }
}
