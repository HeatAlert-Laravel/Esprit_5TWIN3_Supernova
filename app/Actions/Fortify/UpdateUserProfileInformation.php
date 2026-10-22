<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function update(User $user, array $input): void
    {
        $emailChanged = ($input['email'] ?? null) !== $user->email;
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => $emailChanged ? ['required', 'string', 'current_password:web'] : ['nullable'],
        ])->validateWithBag('updateProfileInformation');

        DB::transaction(function () use ($user, $input, $emailChanged): void {
            if ($emailChanged) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                $user->email_verified_at = null;
            }
            $user->fill(['name' => $input['name'], 'email' => $input['email']])->save();
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
