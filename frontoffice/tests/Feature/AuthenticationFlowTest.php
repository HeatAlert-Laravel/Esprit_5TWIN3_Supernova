<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

// Every feature test migrates a fresh in-memory SQLite connection, never the local demo database.
beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('guest forms are available and authenticated residents are redirected to their profile', function () {
    $this->get(route('login'))->assertOk()->assertSee('Forgot your password?');
    $this->get(route('register'))->assertOk();
    $this->get(route('password.request'))->assertOk();
    $this->get(route('password.reset', ['token' => 'example']))->assertOk();

    $resident = User::factory()->create(['role' => 'USER']);
    $this->actingAs($resident);
    foreach (['login', 'register', 'password.request'] as $name) {
        $this->get(route($name))->assertRedirect(route('my-profile'));
    }
    $this->get(route('password.reset', ['token' => 'example']))->assertRedirect(route('my-profile'));
});

test('authenticated admin is redirected away from front office guest forms', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $this->actingAs($admin);
    foreach (['login', 'register', 'password.request'] as $name) {
        $this->get(route($name))->assertRedirect(rtrim(config('app.backoffice_url'), '/').'/admin');
    }
    $this->get(route('password.reset', ['token' => 'example']))
        ->assertRedirect(rtrim(config('app.backoffice_url'), '/').'/admin');
});

test('invalid login retains email and remember choice without authenticating', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $this->from(route('login'))->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
        'remember' => '1',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email', $user->email)
        ->assertSessionHasInput('remember', '1');
    $this->assertGuest();
    $this->get(route('login'))->assertSee('checked', false);
});

test('remember me creates Laravel remember token and recaller cookie only when selected', function () {
    $remembered = User::factory()->create(['password' => 'correct-password', 'remember_token' => null]);
    $response = $this->post(route('login'), [
        'email' => $remembered->email,
        'password' => 'correct-password',
        'remember' => '1',
    ])->assertRedirect(route('my-profile'));

    $this->assertAuthenticatedAs($remembered);
    expect($remembered->fresh()->getRememberToken())->not->toBeNull();
    $response->assertCookie(Auth::guard()->getRecallerName());

    $this->post(route('logout'))->assertRedirect(route('home'));
    $normal = User::factory()->create(['password' => 'correct-password', 'remember_token' => null]);
    $response = $this->post(route('login'), [
        'email' => $normal->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('my-profile'));

    $this->assertAuthenticatedAs($normal);
    expect($normal->fresh()->getRememberToken())->toBeEmpty();
    $response->assertCookieMissing(Auth::guard()->getRecallerName());
});

test('forgot password validates email and sends a broker reset token', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'bad-email'])
        ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'unknown@example.test'])
        ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        expect(Password::broker()->tokenExists($user, $notification->token))->toBeTrue();
        $resetUrl = route('password.reset', ['token' => $notification->token, 'email' => $user->email]);
        expect($notification->toMail($user)->actionUrl)->toBe($resetUrl);
        $this->get($resetUrl)
            ->assertOk()->assertSee($user->email)->assertSee('Reset your password');

        return true;
    });
});

test('valid reset changes the password, rotates remember token, and consumes the reset token', function () {
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'remember_token' => 'previous-remember-token',
    ]);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'))->assertSessionHas('status');

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->getRememberToken())->not->toBe('previous-remember-token');
    expect(Password::broker()->tokenExists($user, $token))->toBeFalse();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'old-password-123'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'new-password-123'])
        ->assertRedirect(route('my-profile'));
    $this->assertAuthenticatedAs($user);
});

test('invalid reset token and password confirmation mismatch are rejected', function () {
    $user = User::factory()->create(['password' => 'old-password-123']);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasErrors('token');

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('old-password-123', $user->fresh()->password))->toBeTrue();
    expect(Password::broker()->tokenExists($user, $token))->toBeTrue();
});
