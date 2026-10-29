<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\LoginRateLimiter;

class AuthenticatedResponse implements LoginResponse, TwoFactorLoginResponse, VerifyEmailResponse
{
    public function toResponse($request)
    {
        // The two-factor controller also finishes login here, after its challenge.
        $request->merge(['email' => Auth::user()->email]);
        app(LoginRateLimiter::class)->clear($request);
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $fallback = $request->user()->role === 'ADMIN' ? route('admin.dashboard') : route('my-profile');
        $intended = $request->session()->pull('url.intended');
        // Only accept paths on this application, including Laravel's absolute intended URLs.
        if (is_string($intended)) {
            $parts = parse_url($intended);
            $origin = parse_url(config('app.url'));
            if ($parts !== false && ! str_contains($intended, '\\') && ! preg_match('/[\x00-\x20]/', $intended)
                && ! isset($parts['user']) && ! isset($parts['pass'])
                && (! isset($parts['host']) || ($parts['host'] === ($origin['host'] ?? null)
                    && ($parts['scheme'] ?? null) === ($origin['scheme'] ?? null)
                    && ($parts['port'] ?? null) === ($origin['port'] ?? null)))) {
                $path = $parts['path'] ?? '/';
                if (str_starts_with($path, '/') && ! str_starts_with($path, '//')) {
                    return redirect($path.(isset($parts['query']) ? '?'.$parts['query'] : ''));
                }
            }
        }

        return redirect($fallback);
    }
}
