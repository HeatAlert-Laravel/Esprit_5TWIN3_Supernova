<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\AccountInformationResponse;
use App\Http\Responses\AuthenticatedResponse;
use App\Http\Responses\RegisteredResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([Contracts\LoginResponse::class, Contracts\TwoFactorLoginResponse::class, Contracts\VerifyEmailResponse::class] as $contract) {
            $this->app->singleton($contract, AuthenticatedResponse::class);
        }
        $this->app->singleton(Contracts\RegisterResponse::class, RegisteredResponse::class);
        $this->app->singleton(Contracts\ProfileInformationUpdatedResponse::class, AccountInformationResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', [
            'token' => $request->route('token'), 'email' => $request->query('email', ''),
        ]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)
            ->by($request->session()->get('login.id').'|'.$request->ip()));

        // Branded French emails. The CTA keeps the real signed URL / reset link, but the
        // raw URL and the token are never printed as visible text.
        VerifyEmail::toMailUsing(fn ($user, string $url) => (new \App\Mail\HeatAlertVerifyEmail($user->name, $url))
            ->to($user->email, $user->name));
        ResetPassword::toMailUsing(fn ($user, string $token) => (new \App\Mail\HeatAlertResetPassword(
            $user->name,
            route('password.reset', ['token' => $token, 'email' => $user->email]),
            (int) config('auth.passwords.users.expire'),
        ))->to($user->email, $user->name));
    }
}
