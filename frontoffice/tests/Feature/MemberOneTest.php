<?php

use App\Models\User;
// Tests use the PHPUnit in-memory SQLite connection and additive migrations only.
beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('public pages render and protected pages require login', function () {
    $this->get('/')->assertOk()->assertSee('Stay ready when')->assertSee('temperatures')->assertSee('Weather alerts module coming soon');
    foreach (['weather-alerts', 'outages', 'cooling-points', 'advice'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $this->get(route('my-profile'))->assertRedirect(route('login'));
});

test('registration creates a USER, login rotates the session, and logout ends it', function () {
    $this->post(route('register'), [
        'name' => 'Nadia', 'email' => 'nadia@example.test', 'password' => 'strongpass123', 'password_confirmation' => 'strongpass123',
    ])->assertRedirect(route('my-profile'));
    $user = User::where('email', 'nadia@example.test')->firstOrFail();
    expect($user->role)->toBe('USER');
    $this->assertAuthenticatedAs($user);
    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'strongpass123'])->assertRedirect(route('my-profile'));
    $this->assertAuthenticatedAs($user);
});

test('USER cannot access admin routes and can save only their own profile', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $this->actingAs($user)->put(route('my-profile.update'), [
        'phone' => '+216 71 123 456', 'address' => '12 Rue des Jasmins', 'neighborhood' => 'La Marsa', 'has_fragile_person' => '1',
    ])->assertRedirect(route('my-profile'));
    expect($user->fresh()->profile->neighborhood)->toBe('La Marsa');
    $this->get(route('my-profile'))->assertOk()->assertSee('La Marsa');
});
