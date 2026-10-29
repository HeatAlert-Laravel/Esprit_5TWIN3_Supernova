<?php

namespace App\Services;

use App\Models\Coupure;
use App\Models\User;
use App\Notifications\OutageStatusNotification;
use Illuminate\Support\Facades\Notification;

class OutageNotifications
{
    /** Called synchronously inside the outage's transaction: both writes commit or roll back together. */
    public function send(Coupure $coupure): void
    {
        $coupure->load('quartier');
        $resolved = $coupure->statut === 'résolue';
        $notification = new OutageStatusNotification([
            'outage_id' => $coupure->id,
            'event' => $resolved ? 'resolved' : 'active',
            'title' => $resolved ? 'Outage Resolved' : 'New Outage Alert',
            // Area targeting is unavailable; do not claim a broadcast affects the recipient personally.
            'message' => $resolved ? 'The reported outage has been resolved.'
                : 'A new active outage has been reported. View the affected area and details for more information.',
            'area' => $coupure->quartier->nom.' — '.$coupure->quartier->ville,
            'outage_type' => $coupure->type === 'prévue' ? 'Planned' : 'Ongoing',
            'status' => $resolved ? 'Resolved' : 'Active',
            'starts_at' => $coupure->date_debut->toIso8601String(),
        ]);

        // Profiles contain free-text neighborhoods, not a reliable user-to-quartier relation.
        // There is no account-active flag: every existing USER account is eligible.
        User::where('role', 'USER')->select('id')->chunkById(200,
            fn ($users) => Notification::send($users, $notification));
    }
}
