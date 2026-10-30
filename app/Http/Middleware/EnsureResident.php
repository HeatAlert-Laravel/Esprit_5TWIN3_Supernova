<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResident
{
    /**
     * Resident-only pages (household profile, equipment, AI analysis).
     * Signed-in users whose role is not USER (ADMIN) are sent back to the dashboard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'USER') {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
