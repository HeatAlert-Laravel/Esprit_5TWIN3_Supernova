<?php

namespace App\Http\Controllers;

use App\Support\AccountAvatar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
            'securityUnlocked' => time() - (int) $request->session()->get('auth.password_confirmed_at', 0)
                < config('auth.password_timeout'),
        ]);
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048']]);
        $user = $request->user();
        $previous = $user->avatar_path;
        $path = $request->file('avatar')->store('avatars', 'public');
        if ($path === false) {
            throw ValidationException::withMessages(['avatar' => 'The picture could not be stored. Please try again.']);
        }
        try {
            $user->forceFill(['avatar_path' => $path])->save();
        } catch (\Throwable $exception) {
            AccountAvatar::delete($path);
            throw $exception;
        }
        AccountAvatar::delete($previous);

        return to_route('account.edit')->with('status', 'Profile picture updated.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $previous = $user->avatar_path;
        $user->forceFill(['avatar_path' => null])->save();
        AccountAvatar::delete($previous);

        return to_route('account.edit')->with('status', 'Profile picture removed.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('deleteAccount', [
            'password' => ['required', 'string', 'current_password:web'],
            'confirmation' => ['required', 'accepted'],
        ]);
        $user = $request->user();
        $avatar = $user->avatar_path;
        // Logout rotates the remember token while the model still exists.
        Auth::logout();
        DB::transaction(function () use ($user): void {
            // Existing foreign keys cascade profile/equipment, bookmarks, votes
            // and reports. Sessions and reset tokens have no user foreign key.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        AccountAvatar::delete($avatar);

        return to_route('home')->with('status', 'Your HeatAlert account has been deleted.');
    }
}
