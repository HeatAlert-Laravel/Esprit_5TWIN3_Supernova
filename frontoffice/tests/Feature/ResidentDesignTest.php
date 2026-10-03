<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;
use App\Support\Readiness;

// Display logic added by the Shade & Signal redesign. In-memory SQLite, additive migrations only.
beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('readiness steps follow the documented rule for guests and residents', function () {
    $guest = Readiness::for(null);
    expect($guest['authenticated'])->toBeFalse()->and($guest['percent'])->toBe(0)->and($guest['steps'])->toHaveCount(3);

    $user = User::factory()->create();
    expect(Readiness::for($user)['percent'])->toBe(33); // account only

    $profile = Profile::factory()->for($user)->create();
    expect(Readiness::for($user->fresh())['percent'])->toBe(67); // + complete profile

    SensitiveEquipment::factory()->for($profile)->create();
    $full = Readiness::for($user->fresh());
    expect($full['percent'])->toBe(100)->and(collect($full['steps'])->pluck('done')->all())->toBe([true, true, true]);

    $profile->update(['address' => '']); // incomplete profile no longer counts
    expect(Readiness::for($user->fresh())['percent'])->toBe(67);
});

test('home shows the status strip as a non-live placeholder and generic steps to guests', function () {
    $this->get('/')->assertOk()
        ->assertSee('Weather alerts module coming soon')->assertSee('Not live data')
        ->assertSee('Prepare my household')->assertSee('Free for residents')
        ->assertSee('Create your account')->assertSee('3 steps')
        ->assertSee('Coming soon')
        ->assertDontSee('°C', false)->assertDontSee('See today');
});

test('home shows real readiness progress to a signed-in resident', function () {
    $user = User::factory()->create();
    Profile::factory()->for($user)->create();

    $this->actingAs($user)->get('/')->assertOk()->assertSee('67%')->assertSee('Done')->assertSee('To do')
        ->assertSee(route('my-profile'), false);
});

test('my profile is a readiness hub with completion, grouped fields and equipment cards', function () {
    $profile = Profile::factory()->create(['neighborhood' => 'Carthage']);
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'Medication fridge', 'type' => 'medical', 'priority_level' => 'high', 'description' => 'Keeps insulin cool']);

    $this->actingAs($profile->user)->get(route('my-profile'))->assertOk()
        ->assertSee($profile->user->name)->assertSee('Complete · 100%')
        ->assertSeeInOrder(['Contact', 'Household', 'Household members'])
        ->assertSee('Medication fridge')->assertSee('Type: medical')->assertSee('Priority: High')->assertSee('Keeps insulin cool')
        ->assertSee('Remove')->assertSee(route('profile.equipment.edit', SensitiveEquipment::first()), false);
});

test('my profile shows 0 percent and the save-first notice before a profile exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('my-profile'))->assertOk()
        ->assertSee('Incomplete · 0%')->assertSee('Save your household details first');
});

test('my profile empty equipment state keeps the original message', function () {
    $profile = Profile::factory()->create();

    $this->actingAs($profile->user)->get(route('my-profile'))->assertOk()->assertSee('No equipment recorded yet.');
});

test('equipment form renders priority radio cards with the existing values and keeps the selection', function () {
    $profile = Profile::factory()->create();
    $item = SensitiveEquipment::factory()->for($profile)->create(['priority_level' => 'high']);

    $this->actingAs($profile->user)->get(route('profile.equipment.edit', $item))->assertOk()
        ->assertSee('name="priority_level" value="low"', false)
        ->assertSee('name="priority_level" value="medium"', false)
        ->assertSee('name="priority_level" value="high" checked', false)
        ->assertDontSee('<select id="priority_level"', false);
});

test('auth pages render the redesigned shells and keep their form behavior', function () {
    $this->get(route('login'))->assertOk()
        ->assertSee('Welcome back')->assertSee('name="remember"', false)->assertSee('Forgot your password?')
        ->assertSee('data-toggle-password="password"', false)->assertSee('Prepare before the heat arrives.');
    $this->get(route('register'))->assertOk()->assertSee('Join HeatAlert')->assertSee('name="password_confirmation"', false);
    $this->get(route('password.request'))->assertOk()->assertSee('Back to login')->assertSee('Send reset link');
    $this->get(route('password.reset', ['token' => 'abc', 'email' => 'a@b.test']))->assertOk()
        ->assertSee('Save new password')->assertSee('name="token" value="abc"', false);
});

test('login errors show a summary and inline message and keep the typed email', function () {
    $this->from(route('login'))->post(route('login'), ['email' => 'nobody@example.test', 'password' => 'wrong-password'])
        ->assertRedirect(route('login'));

    $this->get(route('login'))->assertOk()
        ->assertSee('Please correct the highlighted fields.')->assertSee('nobody@example.test')->assertSee('aria-invalid="true"', false);
});

test('forgot password shows a success state after the reset link is sent', function () {
    $user = User::factory()->create();

    $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'));
    $this->get(route('password.request'))->assertOk()->assertSee('We have emailed your password reset link.');
});

test('navbar marks the active page and offers login and create account to guests', function () {
    $this->get(route('outages'))->assertOk()->assertSee('aria-current="page"', false)->assertSee('Create account')->assertSee('Login');
    $this->get(route('outages'))->assertSee('Outages is coming soon');
});
