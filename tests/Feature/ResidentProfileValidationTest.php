<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;

// Resident-side profile rules and readable display. In-memory SQLite, additive migrations only.
beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

function residentDetails(array $overrides = []): array
{
    return array_merge(['phone' => '+216 20 123 456', 'address' => '10 Main Street', 'neighborhood' => 'Carthage'], $overrides);
}

test('resident profile validation requires fields and a plausible phone number', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $this->actingAs($user);

    $this->put(route('my-profile.update'), [])->assertSessionHasErrors(['phone', 'address', 'neighborhood']);
    $this->put(route('my-profile.update'), residentDetails(['phone' => 'call me']))->assertSessionHasErrors('phone');
    $this->put(route('my-profile.update'), residentDetails(['phone' => '123']))->assertSessionHasErrors('phone');
    $this->put(route('my-profile.update'), residentDetails(['address' => str_repeat('a', 256)]))->assertSessionHasErrors('address');
    $this->put(route('my-profile.update'), residentDetails(['neighborhood' => str_repeat('a', 101)]))->assertSessionHasErrors('neighborhood');
    expect($user->fresh()->profile)->toBeNull();

    $this->put(route('my-profile.update'), residentDetails())->assertRedirect(route('my-profile'))->assertSessionHas('status', 'Profile saved.');
    expect($user->fresh()->profile->phone)->toBe('+216 20 123 456');
});

test('resident pages show names and values, never technical ids', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'Kitchen refrigerator']);

    $this->actingAs($profile->user)->get(route('my-profile'))->assertOk()
        ->assertSee('Kitchen refrigerator')->assertSee($profile->user->email)
        ->assertDontSee('Profile ID')->assertDontSee('User ID')->assertDontSee('Type ID')->assertDontSee('Equipment ID');
});

test('a resident cannot use crafted equipment urls of another resident', function () {
    $mine = Profile::factory()->create();
    $foreign = SensitiveEquipment::factory()->create();
    $data = ['name' => 'Hacked', 'type_equipement_id' => $foreign->type_equipement_id];

    $this->actingAs($mine->user);
    $this->get(route('profile.equipment.edit', $foreign))->assertNotFound();
    $this->put(route('profile.equipment.update', $foreign), $data)->assertNotFound();
    $this->delete(route('profile.equipment.destroy', $foreign))->assertNotFound();
    $this->assertDatabaseHas('sensitive_equipments', ['id' => $foreign->id, 'name' => $foreign->name]);
});

test('an administrator account cannot use the resident equipment pages as someone else', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $foreign = SensitiveEquipment::factory()->create();

    $this->actingAs($admin)->get(route('profile.equipment.edit', $foreign))->assertNotFound();
});
