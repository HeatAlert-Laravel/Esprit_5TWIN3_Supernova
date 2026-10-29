<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\OutageStatusNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OutageNotificationInbox
{
    public static function query(User $user): MorphMany
    {
        return $user->notifications()->where('type', OutageStatusNotification::class);
    }

    public static function for(User $user): array
    {
        return [
            'recentNotifications' => self::query($user)->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get(),
            'unreadCount' => self::query($user)->whereNull('read_at')->count(),
        ];
    }
}
