<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OutageStatusNotification extends Notification
{
    public function __construct(public readonly array $outage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->outage;
    }
}
