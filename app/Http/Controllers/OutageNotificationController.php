<?php

namespace App\Http\Controllers;

use App\Models\Coupure;
use App\Models\User;
use App\Support\OutageNotificationInbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutageNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->snapshot($this->resident($request));
    }

    public function read(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $user = $this->resident($request);
        OutageNotificationInbox::query($user)->whereKey($notification)->firstOrFail()->markAsRead();

        return $request->expectsJson() ? $this->snapshot($user) : back();
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->resident($request);
        OutageNotificationInbox::query($user)->whereNull('read_at')->update(['read_at' => now()]);

        return $request->expectsJson() ? $this->snapshot($user) : back();
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = OutageNotificationInbox::query($this->resident($request))->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $outageId = $item->data['outage_id'];
        if (! Coupure::whereKey($outageId)->exists()) {
            return redirect()->route('outages')->with('status', 'This outage is no longer available.');
        }

        return redirect()->route('outages.show', $outageId);
    }

    private function resident(Request $request): User
    {
        abort_unless($request->user()?->role === 'USER', 403);

        return $request->user();
    }

    private function snapshot(User $user): JsonResponse
    {
        $data = OutageNotificationInbox::for($user);

        return response()->json([
            'unread_count' => $data['unreadCount'],
            'html' => view('partials.outage-notification-items', $data)->render(),
        ])->header('Cache-Control', 'no-store');
    }
}
