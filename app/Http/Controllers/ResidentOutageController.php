<?php

namespace App\Http\Controllers;

use App\Models\Coupure;
use App\Support\OutageNotificationInbox;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentOutageController extends Controller
{
    public function show(Request $request, Coupure $coupure): View
    {
        $user = $request->user();
        abort_unless($user->role === 'USER', 403);
        // Active outages are public. Resolved ones remain available to their recipients or reporters.
        abort_unless($coupure->statut === 'active'
            || OutageNotificationInbox::query($user)->where('data->outage_id', $coupure->id)->exists()
            || $coupure->signalements()->where('user_id', $user->id)->exists(), 404);

        return view('coupures.resident-show', ['coupure' => $coupure->load('quartier')]);
    }
}
