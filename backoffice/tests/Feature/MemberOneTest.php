<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;
use Database\Seeders\ProfileSeeder;
use Database\Seeders\SensitiveEquipmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public pages render and protected pages require login', function () {
    $this->get('/')->assertOk()->assertSee('Stay ready when temperatures rise.');
    foreach (['weather-alerts', 'outages', 'cooling-points', 'advice'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $this->get(route('my-profile'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
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
    $this->actingAs($user)->get(route('admin.profiles.index'))->assertForbidden();
    $this->actingAs($user)->put(route('my-profile.update'), [
        'phone' => '+216 71 123 456', 'address' => '12 Rue des Jasmins', 'neighborhood' => 'La Marsa', 'has_fragile_person' => '1',
    ])->assertRedirect(route('my-profile'));
    expect($user->fresh()->profile->neighborhood)->toBe('La Marsa');
    $this->get(route('my-profile'))->assertOk()->assertSee('La Marsa');
});

test('ADMIN can create, view, update and delete a profile with related equipment', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $resident = User::factory()->create(['role' => 'USER']);
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Community dashboard');
    $this->get(route('admin.profiles.create'))->assertOk()->assertSee($resident->name);
    $this->post(route('admin.profiles.store'), [
        'user_id' => $resident->id, 'phone' => '12345678', 'address' => '10 Main Street', 'neighborhood' => 'Carthage', 'has_fragile_person' => '1',
    ])->assertRedirect();
    $profile = $resident->fresh()->profile;
    expect($profile->has_fragile_person)->toBeTrue();
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'Medical respirator']);
    $this->get(route('admin.profiles.index'))->assertOk()->assertSee('Carthage');
    $this->get(route('admin.profiles.show', $profile))->assertOk()->assertSee('Medical respirator');
    $this->get(route('admin.profiles.edit', $profile))->assertOk()->assertSee('10 Main Street');
    $this->put(route('admin.profiles.update', $profile), [
        'user_id' => $resident->id, 'phone' => '12345678', 'address' => '11 Main Street', 'neighborhood' => 'Carthage', 'has_fragile_person' => '0',
    ])->assertRedirect();
    expect($profile->fresh()->address)->toBe('11 Main Street');
    $this->delete(route('admin.profiles.destroy', $profile))->assertRedirect(route('admin.profiles.index'));
    $this->assertDatabaseMissing('profiles', ['id' => $profile->id]);
    $this->assertDatabaseCount('sensitive_equipments', 0);
});

test('ADMIN can complete equipment CRUD and sees the linked profile', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $profile = Profile::factory()->create();
    $this->actingAs($admin)->get(route('admin.equipment.create'))->assertOk()->assertSee($profile->user->name);
    $this->post(route('admin.equipment.store'), [
        'profile_id' => $profile->id, 'name' => 'Freezer', 'type' => 'household', 'priority_level' => 'high', 'description' => 'Stores medicine',
    ])->assertRedirect();
    $item = SensitiveEquipment::where('name', 'Freezer')->firstOrFail();
    $this->get(route('admin.equipment.index'))->assertOk()->assertSee('Freezer');
    $this->get(route('admin.equipment.show', $item))->assertOk()->assertSee($profile->user->name);
    $this->get(route('admin.equipment.edit', $item))->assertOk()->assertSee('Stores medicine');
    $this->put(route('admin.equipment.update', $item), [
        'profile_id' => $profile->id, 'name' => 'Freezer', 'type' => 'household', 'priority_level' => 'medium', 'description' => 'Updated',
    ])->assertRedirect();
    expect($item->fresh()->priority_level)->toBe('medium');
    $this->delete(route('admin.equipment.destroy', $item))->assertRedirect(route('admin.equipment.index'));
    $this->assertDatabaseMissing('sensitive_equipments', ['id' => $item->id]);
});

test('invalid form data shows errors and preserves prior input', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $profile = Profile::factory()->create();
    $this->actingAs($admin)->from(route('admin.equipment.create'))->post(route('admin.equipment.store'), [
        'profile_id' => $profile->id, 'name' => 'Freezer', 'type' => 'household', 'priority_level' => 'urgent',
    ])->assertRedirect(route('admin.equipment.create'))->assertSessionHasErrors('priority_level')->assertSessionHasInput('name', 'Freezer');
    $this->get(route('admin.equipment.create'))->assertOk()->assertSee('Freezer')->assertSee('The selected priority level is invalid.');
});

test('profile validation rejects missing fields and preserves form input', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $resident = User::factory()->create(['role' => 'USER']);
    $this->actingAs($admin)->from(route('admin.profiles.create'))->post(route('admin.profiles.store'), [
        'user_id' => $resident->id, 'phone' => '12345678', 'address' => '', 'neighborhood' => 'Le Bardo',
    ])->assertRedirect(route('admin.profiles.create'))->assertSessionHasErrors('address')->assertSessionHasInput('neighborhood', 'Le Bardo');
    $this->get(route('admin.profiles.create'))->assertOk()->assertSee('Le Bardo')->assertSee('The address field is required.');
});

test('factories and seeders preserve the one to many relationship', function () {
    $this->seed([ProfileSeeder::class, SensitiveEquipmentSeeder::class]);
    expect(Profile::count())->toBe(3);
    expect(SensitiveEquipment::count())->toBe(6);
    expect(Profile::first()->sensitiveEquipments)->toHaveCount(2);
    expect(SensitiveEquipment::first()->profile->user)->toBeInstanceOf(User::class);
});
