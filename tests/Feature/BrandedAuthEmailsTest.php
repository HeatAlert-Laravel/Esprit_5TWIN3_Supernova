<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Notification::fake();
});

function emailHref(string $html): string
{
    preg_match('/href="([^"]+)"/', $html, $matches);

    return html_entity_decode($matches[1] ?? '', ENT_QUOTES, 'UTF-8');
}

test('the verification email is branded in French with a signed link and no visible raw URL', function () {
    $user = User::factory()->unverified()->create(['name' => 'Résidente Test']);

    $this->actingAs($user)->post(route('verification.send'));
    Notification::assertSentTo($user, VerifyEmail::class);

    $mail = Notification::sent($user, VerifyEmail::class)->first()->toMail($user);
    $html = $mail->render();

    expect($mail->envelope()->subject)->toBe('Confirmez votre adresse e-mail HeatAlert')
        ->and($html)->toContain('HeatAlert')
        ->and($html)->toContain('Confirmez votre adresse e-mail')
        ->and($html)->toContain('Bonjour Résidente Test')
        ->and($html)->toContain('Confirmer mon adresse')
        ->and($html)->toContain('role="presentation"')
        ->and($html)->not->toContain('trouble clicking');

    $href = emailHref($html);
    expect($href)->toContain('/email/verify/')->and($href)->toContain('signature=');

    // The link lives only in the button: no raw URL is ever shown as visible text.
    expect(strip_tags($html))->not->toContain('http');

    $this->get($href)->assertRedirect(route('my-profile'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('the password reset email is branded in French and hides the raw token and URL', function () {
    $user = User::factory()->create(['name' => 'Résident Test', 'password' => 'safe-password-123']);

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);

    $notification = Notification::sent($user, ResetPassword::class)->first();
    $mail = $notification->toMail($user);
    $html = $mail->render();

    expect($mail->envelope()->subject)->toBe('Réinitialiser votre mot de passe HeatAlert')
        ->and($html)->toContain('HeatAlert')
        ->and($html)->toContain('Réinitialiser mon mot de passe')
        ->and($html)->toContain('Bonjour Résident Test')
        ->and($html)->toContain('Ce lien expire dans 60 minutes.')
        ->and($html)->toContain('role="presentation"')
        ->and($html)->not->toContain('trouble clicking');

    $href = emailHref($html);
    expect($href)->toContain('/reset-password/')->and($href)->toContain('email=');

    // The raw token stays inside the href attribute and is never visible as text.
    expect($html)->toContain($notification->token)
        ->and(strip_tags($html))->not->toContain($notification->token)
        ->and(strip_tags($html))->not->toContain('http');

    // The reset page behind the emailed link really opens.
    $this->get($href)->assertOk();
});
