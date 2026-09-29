<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot use any resident equipment route', function () {
    $equipment = SensitiveEquipment::factory()->create();
    $data = ['name' => 'Freezer', 'type' => 'Food', 'priority_level' => 'medium'];

    $this->get(route('profile.equipment.create'))->assertRedirect(route('login'));
    $this->post(route('profile.equipment.store'), $data)->assertRedirect(route('login'));
    $this->get(route('profile.equipment.edit', $equipment))->assertRedirect(route('login'));
    $this->put(route('profile.equipment.update', $equipment), $data)->assertRedirect(route('login'));
    $this->delete(route('profile.equipment.destroy', $equipment))->assertRedirect(route('login'));
});

test('resident without a household profile cannot create equipment', function () {
    $resident = User::factory()->create(['role' => 'USER']);
    $this->actingAs($resident)->get(route('my-profile'))->assertOk()->assertSee('Save your household details first');
    $this->get(route('profile.equipment.create'))->assertNotFound();
    $this->post(route('profile.equipment.store'), [
        'name' => 'Freezer', 'type' => 'Food', 'priority_level' => 'medium',
    ])->assertNotFound();
});

test('resident can create edit and delete only equipment linked to their own profile', function () {
    $profile = Profile::factory()->create();
    $otherProfile = Profile::factory()->create();
    $resident = $profile->user;

    $this->actingAs($resident)->get(route('profile.equipment.create'))
        ->assertOk()->assertDontSee('name="profile_id"', false);

    $this->post(route('profile.equipment.store'), [
        'profile_id' => $otherProfile->id,
        'name' => 'Refrigerator',
        'type' => 'Food',
        'description' => 'Keeps medicine cool',
        'priority_level' => 'medium',
    ])->assertRedirect(route('my-profile'));

    $equipment = SensitiveEquipment::where('name', 'Refrigerator')->firstOrFail();
    expect($equipment->profile_id)->toBe($profile->id);
    expect($profile->fresh()->sensitiveEquipments->pluck('id'))->toContain($equipment->id);

    $this->get(route('my-profile'))->assertOk()
        ->assertSee('Refrigerator')->assertSee('Type: Food')->assertSee('Priority: Medium')
        ->assertSee(route('profile.equipment.edit', $equipment));
    $this->get(route('profile.equipment.edit', $equipment))->assertOk()
        ->assertSee('Keeps medicine cool')->assertDontSee('name="profile_id"', false);

    $this->put(route('profile.equipment.update', $equipment), [
        'profile_id' => $otherProfile->id,
        'name' => 'Medical refrigerator',
        'type' => 'Medical',
        'description' => 'Updated',
        'priority_level' => 'high',
    ])->assertRedirect(route('my-profile'));
    expect($equipment->fresh()->profile_id)->toBe($profile->id);
    $this->get(route('my-profile'))->assertOk()->assertSee('Medical refrigerator')->assertSee('Priority: High');

    $this->delete(route('profile.equipment.destroy', $equipment))->assertRedirect(route('my-profile'));
    $this->assertDatabaseMissing('sensitive_equipments', ['id' => $equipment->id]);
    $this->get(route('my-profile'))->assertOk()->assertSee('No equipment recorded yet.')->assertDontSee('Medical refrigerator');
});

test('resident cannot read edit update or delete another residents equipment', function () {
    $profile = Profile::factory()->create();
    $foreign = SensitiveEquipment::factory()->create(['name' => 'Foreign equipment']);
    $resident = $profile->user;

    $this->actingAs($resident)->get(route('my-profile'))->assertOk()->assertDontSee('Foreign equipment');
    $this->get(route('admin.equipment.show', $foreign))->assertForbidden();
    $this->get(route('profile.equipment.edit', $foreign))->assertNotFound();
    $this->put(route('profile.equipment.update', $foreign), [
        'name' => 'Changed', 'type' => 'Medical', 'priority_level' => 'high',
    ])->assertNotFound();
    $this->delete(route('profile.equipment.destroy', $foreign))->assertNotFound();
    expect($foreign->fresh()->name)->toBe('Foreign equipment');
});

test('resident equipment validation shows errors and retains old input', function () {
    $profile = Profile::factory()->create();
    $this->actingAs($profile->user)->from(route('profile.equipment.create'))
        ->post(route('profile.equipment.store'), [
            'name' => 'Aquarium', 'type' => 'Household', 'priority_level' => 'urgent',
        ])->assertRedirect(route('profile.equipment.create'))
        ->assertSessionHasErrors('priority_level')
        ->assertSessionHasInput('name', 'Aquarium');

    $this->get(route('profile.equipment.create'))->assertOk()
        ->assertSee('Aquarium')->assertSee('The selected priority level is invalid.');
    $this->assertDatabaseCount('sensitive_equipments', 0);
});
