<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\User;

// Tests use the PHPUnit in-memory SQLite connection and additive migrations only.
// The canonical equipment types (Refrigerator, Medical equipment, Fan, ...) are created by the migrations.
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->type = fn (string $name): TypeEquipement => TypeEquipement::where('name', $name)->firstOrFail();
});

test('guest is redirected from every protected equipment page', function () {
    $equipment = SensitiveEquipment::factory()->create();
    $data = ['name' => 'Freezer', 'type_equipement_id' => ($this->type)('Freezer')->id];

    $this->get(route('my-profile'))->assertRedirect(route('login'));
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
        'name' => 'Freezer', 'type_equipement_id' => ($this->type)('Freezer')->id,
    ])->assertNotFound();
});

test('resident can create edit and delete only equipment linked to their own profile', function () {
    $profile = Profile::factory()->create();
    $otherProfile = Profile::factory()->create();
    $resident = $profile->user;
    $fridge = ($this->type)('Refrigerator');
    $medical = ($this->type)('Medical equipment');

    $this->actingAs($resident)->get(route('profile.equipment.create'))
        ->assertOk()->assertDontSee('name="profile_id"', false);

    $this->post(route('profile.equipment.store'), [
        'profile_id' => $otherProfile->id,
        'name' => 'Kitchen refrigerator',
        'type_equipement_id' => $fridge->id,
        'description' => 'Keeps medicine cool',
    ])->assertRedirect(route('my-profile'));

    $equipment = SensitiveEquipment::where('name', 'Kitchen refrigerator')->firstOrFail();
    expect($equipment->profile_id)->toBe($profile->id);
    expect($equipment->type_equipement_id)->toBe($fridge->id);
    expect($profile->fresh()->sensitiveEquipments->pluck('id'))->toContain($equipment->id);

    $this->get(route('my-profile'))->assertOk()
        ->assertSee('Kitchen refrigerator')->assertSee('Type: Refrigerator')->assertSee('Risk: HIGH')
        ->assertSee(route('profile.equipment.edit', $equipment));
    $this->get(route('profile.equipment.edit', $equipment))->assertOk()
        ->assertSee('Keeps medicine cool')->assertDontSee('name="profile_id"', false);

    $this->put(route('profile.equipment.update', $equipment), [
        'profile_id' => $otherProfile->id,
        'name' => 'Insulin refrigerator',
        'type_equipement_id' => $medical->id,
        'description' => 'Updated',
    ])->assertRedirect(route('my-profile'));
    expect($equipment->fresh()->profile_id)->toBe($profile->id);
    expect($equipment->fresh()->type_equipement_id)->toBe($medical->id);
    $this->get(route('my-profile'))->assertOk()->assertSee('Insulin refrigerator')->assertSee('Type: Medical equipment')->assertSee('Risk: CRITICAL');

    $this->delete(route('profile.equipment.destroy', $equipment))->assertRedirect(route('my-profile'));
    $this->assertDatabaseMissing('sensitive_equipments', ['id' => $equipment->id]);
    $this->get(route('my-profile'))->assertOk()->assertSee('No equipment recorded yet.')->assertDontSee('Insulin refrigerator');
    // Deleting equipment never deletes its type.
    $this->assertDatabaseHas('type_equipements', ['id' => $medical->id]);
});

test('resident sees only their own equipment', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'My own fan']);
    SensitiveEquipment::factory()->create(['name' => 'Neighbour equipment']);

    $this->actingAs($profile->user)->get(route('my-profile'))->assertOk()
        ->assertSee('My own fan')->assertDontSee('Neighbour equipment');
});

test('resident cannot read edit update or delete another residents equipment', function () {
    $profile = Profile::factory()->create();
    $foreign = SensitiveEquipment::factory()->create(['name' => 'Foreign equipment']);
    $resident = $profile->user;

    $this->actingAs($resident)->get(route('my-profile'))->assertOk()->assertDontSee('Foreign equipment');
    $this->get(route('profile.equipment.edit', $foreign))->assertNotFound();
    $this->put(route('profile.equipment.update', $foreign), [
        'name' => 'Changed', 'type_equipement_id' => ($this->type)('Fan')->id,
    ])->assertNotFound();
    $this->delete(route('profile.equipment.destroy', $foreign))->assertNotFound();
    expect($foreign->fresh()->name)->toBe('Foreign equipment');
    $this->assertDatabaseHas('sensitive_equipments', ['id' => $foreign->id]);
});

test('equipment form lists the real equipment types and no free-text type', function () {
    $profile = Profile::factory()->create();
    $item = SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Freezer'))->create();
    $extra = TypeEquipement::factory()->create(['name' => 'Oxygen concentrator']);

    $this->actingAs($profile->user)->get(route('profile.equipment.create'))->assertOk()
        ->assertSee('name="type_equipement_id"', false)
        ->assertSee('Refrigerator')->assertSee('Medical equipment')->assertSee('Oxygen concentrator')
        ->assertDontSee('name="type"', false)->assertDontSee('priority_level');

    $this->get(route('profile.equipment.edit', $item))->assertOk()
        ->assertSee('value="'.$item->type_equipement_id.'" selected', false);
});

test('equipment validation rejects a missing or unknown type and retains old input', function () {
    $profile = Profile::factory()->create();
    $this->actingAs($profile->user);
    $this->post(route('profile.equipment.store'), ['name' => 'Aquarium'])->assertSessionHasErrors('type_equipement_id');
    $this->post(route('profile.equipment.store'), ['type_equipement_id' => ($this->type)('Aquarium')->id])->assertSessionHasErrors('name');

    $this->from(route('profile.equipment.create'))
        ->post(route('profile.equipment.store'), [
            'name' => 'Aquarium', 'type_equipement_id' => 999999,
        ])->assertRedirect(route('profile.equipment.create'))
        ->assertSessionHasErrors('type_equipement_id')
        ->assertSessionHasInput('name', 'Aquarium');

    $this->get(route('profile.equipment.create'))->assertOk()
        ->assertSee('Aquarium')->assertSee('The selected type is invalid.');
    $this->assertDatabaseCount('sensitive_equipments', 0);
});

test('preparedness messages follow the type sensitivities', function () {
    $profile = Profile::factory()->create();
    $both = SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Refrigerator'))->create(['name' => 'Fridge']);
    $outageOnly = SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Fan'))->create(['name' => 'Desk fan']);
    $neither = SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Other'))->create(['name' => 'Misc device']);
    $heatOnly = SensitiveEquipment::factory()->for($profile)->ofType(TypeEquipement::factory()->create([
        'name' => 'Heated terrarium', 'sensitive_to_heat' => true, 'sensitive_to_outage' => false,
    ]))->create(['name' => 'Terrarium']);

    $electricity = 'This equipment depends on electricity. Consider a backup power plan.';
    $heat = 'This equipment may require protection during high temperatures.';

    expect($both->load('typeEquipement')->preparednessMessages())->toBe([$electricity, $heat])
        ->and($outageOnly->load('typeEquipement')->preparednessMessages())->toBe([$electricity])
        ->and($heatOnly->load('typeEquipement')->preparednessMessages())->toBe([$heat])
        ->and($neither->load('typeEquipement')->preparednessMessages())->toBe([]);

    $this->actingAs($profile->user)->get(route('my-profile'))->assertOk()
        ->assertSee($electricity)->assertSee($heat)
        ->assertSee('Heat-sensitive')->assertSee('Outage-sensitive')->assertSee('Not heat-sensitive');
});

test('my profile lists equipment without N+1 queries', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->count(5)->create();

    DB::enableQueryLog();
    $this->actingAs($profile->user)->get(route('my-profile'))->assertOk();
    $typeQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "type_equipements"'))->count();

    expect($typeQueries)->toBe(1);
});
