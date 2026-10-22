<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Notification::fake();
});

function confirmedTwoFactorUser(string $role = 'USER'): User
{
    $user = User::factory()->create(['password' => 'safe-password-123', 'role' => $role]);
    app(EnableTwoFactorAuthentication::class)($user);
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    return $user;
}

test('all two factor management endpoints require password confirmation', function (string $method, string $name) {
    $user = confirmedTwoFactorUser();
    $before = $user->fresh()->getRawOriginal('two_factor_secret');
    $this->actingAs($user)->{$method}(route($name))->assertRedirect(route('password.confirm'));
    expect($user->fresh()->getRawOriginal('two_factor_secret'))->toBe($before);
})->with([
    ['post', 'two-factor.enable'], ['post', 'two-factor.confirm'], ['delete', 'two-factor.disable'],
    ['get', 'two-factor.qr-code'], ['get', 'two-factor.secret-key'], ['get', 'two-factor.recovery-codes'],
    ['post', 'two-factor.regenerate-recovery-codes'],
]);

test('password confirmation validates current password and unlocks the intended security page', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $this->actingAs($user)->get(route('account.security'))->assertRedirect(route('password.confirm'));
    $this->get(route('password.confirm'))->assertOk()->assertSee('Confirm your password');
    $this->post(route('password.confirm.store'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    $this->post(route('password.confirm.store'), ['password' => 'safe-password-123'])->assertRedirect(route('account.security'));
    expect(session('auth.password_confirmed_at'))->toBeGreaterThan(0);
    $this->get(route('account.security'))->assertOk()->assertSee('Enable 2FA');
});

test('enabling 2FA encrypts secrets provides a QR and requires a valid TOTP confirmation', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('account.security'))->post(route('two-factor.enable'))
        ->assertRedirect(route('account.security'))->assertSessionHas('status', 'two-factor-authentication-enabled');
    $user->refresh();
    $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
    expect($user->two_factor_secret)->not->toBe($secret)->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->recoveryCodes())->toHaveCount(8);
    expect($user->toArray())->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes']);
    $this->get(route('two-factor.qr-code'))->assertOk()->assertJsonStructure(['svg', 'url']);
    $this->get(route('two-factor.secret-key'))->assertOk()->assertJsonPath('secretKey', $secret);
    $this->get(route('account.security'))->assertOk()->assertSee('<svg', false)->assertSee('Confirm 2FA');
    $this->post(route('two-factor.confirm'), ['code' => 'bad-code'])
        ->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
    $code = app(Google2FA::class)->getCurrentOtp($secret);
    $this->post(route('two-factor.confirm'), ['code' => $code])->assertSessionHas('status', 'two-factor-authentication-confirmed');
    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
    $this->get(route('account.security'))->assertOk()->assertSee('Show recovery codes');
});

test('account settings never render two factor secrets after password confirmation has expired', function () {
    $user = confirmedTwoFactorUser();
    $secret = decrypt($user->two_factor_secret);
    $code = $user->recoveryCodes()[0];
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - config('auth.password_timeout') - 1])
        ->get(route('account.edit'))->assertOk()->assertDontSee($secret)->assertDontSee($code)
        ->assertSee('Confirm password to manage security');
    $this->get(route('account.security'))->assertRedirect(route('password.confirm'));
    $this->get(route('two-factor.recovery-codes'))->assertRedirect(route('password.confirm'));
});

test('unconfirmed 2FA setup does not lock users out with a login challenge', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    app(EnableTwoFactorAuthentication::class)($user);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123'])->assertRedirect(route('my-profile'));
    $this->assertAuthenticatedAs($user);
});

test('2FA login keeps users unauthenticated until a correct TOTP then preserves remember me and intended destination', function (string $role) {
    $user = confirmedTwoFactorUser($role);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123', 'remember' => '1'])
        ->assertRedirect(route('two-factor.login'))->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
    $this->get(route('account.edit'))->assertRedirect(route('login'));
    $this->get(route('two-factor.login'))->assertOk()->assertSee('Recovery code');
    $this->post(route('two-factor.login.store'), ['code' => 'bad-code'])->assertSessionHasErrors('code');
    $this->assertGuest();
    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('account.edit'))
        ->assertCookie(Auth::guard()->getRecallerName())->assertSessionMissing('login.id');
    $this->assertAuthenticatedAs($user);
})->with(['USER', 'ADMIN']);

test('recovery codes complete a challenge once and are consumed', function () {
    $user = confirmedTwoFactorUser();
    $code = $user->recoveryCodes()[0];
    $credentials = ['email' => $user->email, 'password' => 'safe-password-123'];
    $this->post(route('login.store'), $credentials)->assertRedirect(route('two-factor.login'));
    $this->post(route('two-factor.login.store'), ['recovery_code' => $code])->assertRedirect(route('my-profile'));
    expect($user->fresh()->recoveryCodes())->not->toContain($code);
    $this->post(route('logout'));
    $this->post(route('login.store'), $credentials)->assertRedirect(route('two-factor.login'));
    $this->post(route('two-factor.login.store'), ['recovery_code' => $code])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();
});

test('2FA completion without an intended destination uses the user role', function (string $role) {
    $user = confirmedTwoFactorUser($role);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123'])->assertRedirect(route('two-factor.login'));
    $this->post(route('two-factor.login.store'), ['recovery_code' => $user->recoveryCodes()[0]])
        ->assertRedirect(route($role === 'ADMIN' ? 'admin.dashboard' : 'my-profile'));
    $this->assertAuthenticatedAs($user);
})->with(['USER', 'ADMIN']);

test('2FA challenge is throttled and cannot be started without a password login', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
    $user = confirmedTwoFactorUser();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123']);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('two-factor.login.store'), ['code' => 'bad-code'])->assertSessionHasErrors('code');
    }
    $this->post(route('two-factor.login.store'), ['code' => 'bad-code'])->assertTooManyRequests();
    $this->assertGuest();
});

test('recovery code regeneration invalidates previous codes and disabling clears all 2FA attributes', function () {
    $user = confirmedTwoFactorUser();
    $previous = $user->recoveryCodes();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->get(route('two-factor.recovery-codes'))->assertOk()->assertExactJson($previous);
    $this->post(route('two-factor.regenerate-recovery-codes'))->assertSessionHas('status', 'recovery-codes-generated');
    $next = $user->fresh()->recoveryCodes();
    expect($next)->toHaveCount(8)->and(array_intersect($previous, $next))->toBe([]);
    $this->delete(route('two-factor.disable'))->assertSessionHas('status', 'two-factor-authentication-disabled');
    expect($user->fresh()->two_factor_secret)->toBeNull()->and($user->fresh()->two_factor_recovery_codes)->toBeNull()
        ->and($user->fresh()->two_factor_confirmed_at)->toBeNull();
    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123'])->assertRedirect(route('my-profile'));
});
