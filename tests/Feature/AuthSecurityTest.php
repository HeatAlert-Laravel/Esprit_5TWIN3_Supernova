<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\LoginRateLimiter;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Notification::fake();
});

test('registration forces USER hashes the password and sends verification before granting personal access', function () {
    $this->get(route('register'))->assertOk();
    $this->post(route('register.store'), [
        'name' => 'New Resident', 'email' => 'NEW@example.test',
        'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
        'role' => 'ADMIN', 'email_verified_at' => now(), 'two_factor_secret' => 'forged',
    ])->assertRedirect(route('verification.notice'));
    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->role)->toBe('USER')->and($user->email_verified_at)->toBeNull()
        ->and($user->two_factor_secret)->toBeNull()
        ->and(Hash::check('safe-password-123', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get(route('my-profile'))->assertRedirect(route('verification.notice'));
});

test('registration validates identity and password', function (array $invalid, string $field) {
    User::factory()->create(['email' => 'taken@example.test']);
    $this->post(route('register.store'), array_replace([
        'name' => 'Resident', 'email' => 'new@example.test',
        'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
    ], $invalid))->assertSessionHasErrors($field);
    expect(User::count())->toBe(1);
    $this->assertGuest();
})->with([
    [['name' => ''], 'name'], [['email' => 'invalid'], 'email'],
    [['email' => 'taken@example.test'], 'email'], [['email' => str_repeat('a', 260).'@example.test'], 'email'],
    [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    [['password_confirmation' => 'mismatch'], 'password'],
]);

test('login regenerates session and logout invalidates it and rotates the csrf token', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $this->get(route('login'));
    $session = session()->getId();
    $csrf = session()->token();
    $this->withSession(['private_marker' => 'remove-on-logout'])->post(route('login.store'), [
        'email' => $user->email, 'password' => 'safe-password-123',
    ])->assertRedirect(route('my-profile'));
    expect(session()->getId())->not->toBe($session);
    $this->post(route('logout'))->assertRedirect(route('home'))->assertSessionMissing('private_marker');
    expect(session()->token())->not->toBe($csrf);
    $this->assertGuest();
});

test('login limits five failed normalized email and IP attempts then recovers after one minute', function () {
    $user = User::factory()->create(['email' => 'resident@example.test', 'password' => 'safe-password-123']);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), ['email' => ' RESIDENT@example.test ', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
    }
    $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123']);
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Too many');
    $this->assertGuest();
    $this->travel(61)->seconds();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'safe-password-123'])
        ->assertRedirect(route('my-profile'));
});

test('successful authentication clears the failed login counter', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $credentials = ['email' => $user->email, 'password' => 'wrong'];
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
    }
    $this->post(route('login.store'), [...$credentials, 'password' => 'safe-password-123'])->assertRedirect(route('my-profile'));
    expect(app(LoginRateLimiter::class)->attempts(request()))->toBe(0);
    $this->post(route('logout'));
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
        expect(session('errors')->first('email'))->not->toContain('Too many');
    }
});

test('login has safe intended redirects and keeps role destinations', function (string $role, ?string $intended, string $destination) {
    $user = User::factory()->create(['role' => $role, 'password' => 'safe-password-123']);
    $this->withSession(['url.intended' => $intended])->post(route('login.store'), [
        'email' => $user->email, 'password' => 'safe-password-123',
    ])->assertRedirect($destination === 'account' ? '/account' : route($destination));
})->with([
    ['USER', null, 'my-profile'], ['ADMIN', null, 'admin.dashboard'],
    ['USER', '/account', 'account'], ['USER', 'https://attacker.example/path', 'my-profile'],
    ['USER', '//attacker.example/path', 'my-profile'], ['USER', '/\\attacker.example/path', 'my-profile'],
]);

test('email notice resend signed link and verified event complete the verification flow', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create(['role' => 'USER']);
    $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee($user->email);
    $this->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);
    $this->get($url)->assertRedirect(route('my-profile'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
    $this->get(route('my-profile'))->assertOk();
    $this->get(route('verification.notice'))->assertRedirect(config('fortify.home'));
});

test('invalid expired wrong identity and wrong email verification links cannot verify a user', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    foreach ([
        route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]),
        URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]),
        URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id + 1, 'hash' => sha1($user->email)]),
        URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.test')]),
    ] as $url) {
        $this->get($url)->assertForbidden();
        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    }
});

test('verification resend is throttled', function () {
    $this->actingAs(User::factory()->unverified()->create());
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->post(route('verification.send'))->assertRedirect();
    }
    $this->post(route('verification.send'))->assertTooManyRequests();
});

test('unverified users can manage account but cannot use personal actions and public pages remain public', function () {
    $this->actingAs(User::factory()->unverified()->create(['role' => 'USER']));
    $this->get(route('account.edit'))->assertOk()->assertSee('Unverified');
    foreach (['my-profile', 'profile.equipment.create', 'outages.report.create', 'advice.saved'] as $name) {
        $this->get(route($name))->assertRedirect(route('verification.notice'));
    }
    foreach (['home', 'weather-alerts', 'advice', 'cooling-points'] as $name) {
        $this->get(route($name))->assertOk();
    }
});

test('admin authorization is enforced and admins also require verified email', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'USER']))->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(User::factory()->unverified()->create(['role' => 'ADMIN']))->get(route('admin.dashboard'))->assertRedirect(route('verification.notice'));
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->get(route('admin.dashboard'))->assertOk();
});

test('password reset links are throttled and expire after sixty minutes without changing the password', function () {
    $user = User::factory()->create(['password' => 'safe-password-123']);
    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
    $notification = Notification::sent($user, ResetPassword::class)->first();
    expect(DB::table('password_reset_tokens')->where('email', $user->email)->value('token'))->not->toBe($notification->token);
    $this->travel(61)->minutes();
    $this->post(route('password.update'), [
        'email' => $user->email, 'token' => $notification->token,
        'password' => 'changed-password-123', 'password_confirmation' => 'changed-password-123',
    ])->assertSessionHasErrors('email');
    expect(Hash::check('safe-password-123', $user->fresh()->password))->toBeTrue();
});

test('password reset rejects a token belonging to a different email', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $token = Password::broker()->createToken($user);
    $hash = $other->password;
    $this->post(route('password.update'), [
        'email' => $other->email, 'token' => $token,
        'password' => 'changed-password-123', 'password_confirmation' => 'changed-password-123',
    ])->assertSessionHasErrors('email');
    expect($other->fresh()->password)->toBe($hash);
});

test('auth routes have unique method URI and name combinations and resolve real controllers', function () {
    $keys = [];
    $names = [];
    foreach (app('router')->getRoutes() as $route) {
        foreach ($route->methods() as $method) {
            $key = $method.' '.$route->uri();
            expect(isset($keys[$key]))->toBeFalse();
            $keys[$key] = true;
        }
        if ($name = $route->getName()) {
            expect(isset($names[$name]))->toBeFalse();
            $names[$name] = true;
        }
        if ($controller = $route->getAction('controller')) {
            [$class, $method] = explode('@', $controller);
            expect(class_exists($class))->toBeTrue();
            expect(method_exists($class, $method))->toBeTrue();
        }
    }
});

test('role is not mass assignable so forged payloads can never create an administrator', function () {
    expect((new User)->getFillable())->not->toContain('role');

    $created = User::create([
        'name' => 'Mass Assignment Attempt',
        'email' => 'mass-assignment@example.test',
        'password' => 'safe-password-123',
        'role' => 'ADMIN',
    ]);
    expect($created->fresh()->role)->toBe('USER');

    $created->fill(['role' => 'ADMIN'])->save();
    expect($created->fresh()->role)->toBe('USER');

    $this->post(route('register.store'), [
        'name' => 'Forged Admin', 'email' => 'forged-admin@example.test',
        'password' => 'safe-password-123', 'password_confirmation' => 'safe-password-123',
        'role' => 'ADMIN',
    ])->assertRedirect(route('verification.notice'));
    expect(User::where('email', 'forged-admin@example.test')->firstOrFail()->role)->toBe('USER');
});
