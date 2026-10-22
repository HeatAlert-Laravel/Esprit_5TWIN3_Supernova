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
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(fn ($user, string $url) => (new MailMessage)
            ->subject('Verify your HeatAlert email address')->greeting('Hello '.$user->name.',')
            ->line('Verify your email address to manage your HeatAlert household and equipment.')
            ->action('Verify email address', $url)
            ->line('If you did not create this account, no action is required.'));
        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Reset your HeatAlert password')->greeting('Hello '.$user->name.',')
            ->line('We received a password reset request for your HeatAlert account.')
            ->action('Reset password', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('This link expires in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you did not request a reset, no action is required.'));
    }
}
