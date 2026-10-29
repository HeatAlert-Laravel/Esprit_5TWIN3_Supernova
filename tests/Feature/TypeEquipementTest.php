<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\User;

// Module 5 parent entity. In-memory SQLite, additive migrations only (they create the canonical types).
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
});

function validType(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dehumidifier', 'risk_level' => 'low', 'sensitive_to_heat' => '0', 'sensitive_to_outage' => '1',
    ], $overrides);
}

test('guest is redirected and USER is forbidden from the equipment type pages', function () {
    $type = TypeEquipement::first();
    $user = User::factory()->create(['role' => 'USER']);

    $this->get(route('admin.type-equipements.index'))->assertRedirect(route('login'));
    $this->post(route('admin.type-equipements.store'), validType())->assertRedirect(route('login'));

    $this->actingAs($user);
    $this->get(route('admin.type-equipements.index'))->assertForbidden();
    $this->get(route('admin.type-equipements.create'))->assertForbidden();
    $this->post(route('admin.type-equipements.store'), validType())->assertForbidden();
    $this->get(route('admin.type-equipements.show', $type))->assertForbidden();
    $this->get(route('admin.type-equipements.edit', $type))->assertForbidden();
    $this->put(route('admin.type-equipements.update', $type), validType())->assertForbidden();
    $this->delete(route('admin.type-equipements.destroy', $type))->assertForbidden();
    $this->assertDatabaseMissing('type_equipements', ['name' => 'Dehumidifier']);
});

test('index lists types with sensitivities, risk, equipment count and updated date', function () {
    $fridge = TypeEquipement::where('name', 'Refrigerator')->firstOrFail();
    SensitiveEquipment::factory()->count(3)->ofType($fridge)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.type-equipements.index'))->assertOk()
        ->assertSeeInOrder(['Name', 'Heat', 'Outage', 'Risk', 'Equipment', 'Updated'])
        ->assertSee('Refrigerator')->assertSee('Medical equipment')->assertSee('CRITICAL')->assertSee('7 types');

    $row = $response->viewData('types')->firstWhere('name', 'Refrigerator');
    expect($row->sensitive_equipments_count)->toBe(3);
});

test('index search filters by name and has an empty state', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.type-equipements.index', ['q' => 'fridge']))->assertOk()->assertDontSee('Refrigerator'); // "Refrigerator" has no "fridge"
    $this->get(route('admin.type-equipements.index', ['q' => 'Freez']))->assertOk()->assertSee('Freezer')->assertDontSee('Aquarium');
    $this->get(route('admin.type-equipements.index', ['q' => 'zzz']))->assertOk()->assertSee('No equipment types found');
});

test('ADMIN can create, view, edit and update an equipment type', function () {
    $this->actingAs($this->admin)->get(route('admin.type-equipements.create'))->assertOk()
        ->assertSee('Add equipment type')->assertSee('name="risk_level"', false)->assertSee('name="sensitive_to_heat"', false);

    $this->post(route('admin.type-equipements.store'), validType())->assertRedirect();
    $type = TypeEquipement::where('name', 'Dehumidifier')->firstOrFail();
    expect($type->risk_level)->toBe('low')->and($type->sensitive_to_heat)->toBeFalse()->and($type->sensitive_to_outage)->toBeTrue();

    $this->get(route('admin.type-equipements.show', $type))->assertOk()->assertSee('Dehumidifier')->assertSee('Risk: LOW')->assertSee('Not heat-sensitive')->assertSee('Outage-sensitive');
    $this->get(route('admin.type-equipements.edit', $type))->assertOk()->assertSee('value="Dehumidifier"', false);

    // Unchecked checkboxes are simply absent from the request: they must save as false.
    $this->put(route('admin.type-equipements.update', $type), ['name' => 'Dehumidifier XL', 'risk_level' => 'high', 'sensitive_to_heat' => '1'])
        ->assertRedirect(route('admin.type-equipements.show', $type));
    $type->refresh();
    expect($type->name)->toBe('Dehumidifier XL')->and($type->risk_level)->toBe('high')
        ->and($type->sensitive_to_heat)->toBeTrue()->and($type->sensitive_to_outage)->toBeFalse();
});

test('type validation rejects bad input and keeps old values', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.type-equipements.store'), [])->assertSessionHasErrors(['name', 'risk_level']);
    $this->post(route('admin.type-equipements.store'), validType(['risk_level' => 'urgent']))->assertSessionHasErrors('risk_level');
    $this->post(route('admin.type-equipements.store'), validType(['name' => str_repeat('a', 101)]))->assertSessionHasErrors('name');
    $this->post(route('admin.type-equipements.store'), validType(['name' => 'Refrigerator']))->assertSessionHasErrors('name'); // unique
    $this->post(route('admin.type-equipements.store'), validType(['sensitive_to_heat' => 'maybe']))->assertSessionHasErrors('sensitive_to_heat');

    $this->from(route('admin.type-equipements.create'))->post(route('admin.type-equipements.store'), validType(['name' => 'Wine cellar', 'risk_level' => 'nope']))
        ->assertRedirect(route('admin.type-equipements.create'))->assertSessionHasInput('name', 'Wine cellar');
    $this->get(route('admin.type-equipements.create'))->assertOk()->assertSee('Wine cellar')->assertSee('The selected risk level is invalid.');
    $this->assertDatabaseMissing('type_equipements', ['name' => 'Wine cellar']);
});

test('updating a type may keep its own name but not take another types name', function () {
    $fan = TypeEquipement::where('name', 'Fan')->firstOrFail();
    $this->actingAs($this->admin);

    $this->put(route('admin.type-equipements.update', $fan), validType(['name' => 'Fan']))->assertSessionHasNoErrors();
    $this->put(route('admin.type-equipements.update', $fan), validType(['name' => 'Freezer']))->assertSessionHasErrors('name');
    expect($fan->fresh()->name)->toBe('Fan');
});

test('an unused type can be deleted', function () {
    $type = TypeEquipement::factory()->create(['name' => 'Wine cellar']);

    $this->actingAs($this->admin)->delete(route('admin.type-equipements.destroy', $type))
        ->assertRedirect(route('admin.type-equipements.index'))->assertSessionHas('status', 'Equipment type deleted.');
    $this->assertDatabaseMissing('type_equipements', ['id' => $type->id]);
});

test('a type used by equipment cannot be deleted and its equipment is kept', function () {
    $type = TypeEquipement::where('name', 'Freezer')->firstOrFail();
    $items = SensitiveEquipment::factory()->count(2)->ofType($type)->create();

    $this->actingAs($this->admin)->delete(route('admin.type-equipements.destroy', $type))
        ->assertRedirect(route('admin.type-equipements.show', $type))
        ->assertSessionHas('error', 'This equipment type is currently used by 2 equipment records.');

    $this->assertDatabaseHas('type_equipements', ['id' => $type->id]);
    expect(SensitiveEquipment::whereIn('id', $items->pluck('id'))->count())->toBe(2);
    $this->get(route('admin.type-equipements.show', $type))->assertOk()
        ->assertSee('This equipment type is currently used by 2 equipment records.');

    // Singular wording for one record.
    $single = TypeEquipement::where('name', 'Aquarium')->firstOrFail();
    SensitiveEquipment::factory()->ofType($single)->create();
    $this->delete(route('admin.type-equipements.destroy', $single))
        ->assertSessionHas('error', 'This equipment type is currently used by 1 equipment record.');
});

test('the database itself refuses to delete a used type (no cascade)', function () {
    $type = TypeEquipement::where('name', 'Fan')->firstOrFail();
    SensitiveEquipment::factory()->ofType($type)->create();

    expect(fn () => $type->delete())->toThrow(Illuminate\Database\QueryException::class);
    expect(SensitiveEquipment::count())->toBe(1);
});

test('type detail shows the equipment using the type through the hasMany relation', function () {
    $type = TypeEquipement::where('name', 'Medical equipment')->firstOrFail();
    $owner = Profile::factory()->create(['neighborhood' => 'Carthage']);
    SensitiveEquipment::factory()->for($owner)->ofType($type)->create(['name' => 'Home respirator']);
    SensitiveEquipment::factory()->ofType(TypeEquipement::where('name', 'Fan')->firstOrFail())->create(['name' => 'Unrelated fan']);

    $this->actingAs($this->admin)->get(route('admin.type-equipements.show', $type))->assertOk()
        ->assertSee('Medical equipment')->assertSee('Risk: CRITICAL')->assertSee('Heat-sensitive')->assertSee('Outage-sensitive')
        ->assertSee('Created')->assertSee('Last updated')
        ->assertSee('Equipment using this type')->assertSee('Home respirator')->assertSee($owner->user->name)->assertSee('Carthage')
        ->assertDontSee('Unrelated fan');
});

test('type detail lists equipment without N+1 queries', function () {
    $type = TypeEquipement::where('name', 'Refrigerator')->firstOrFail();
    SensitiveEquipment::factory()->count(6)->ofType($type)->create();

    DB::enableQueryLog();
    $this->actingAs($this->admin)->get(route('admin.type-equipements.show', $type))->assertOk();
    $profileQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "profiles" where "profiles"."id" in'))->count();

    expect($profileQueries)->toBe(1);
});

test('sidebar links to equipment types with the real count', function () {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
        ->assertSee(route('admin.type-equipements.index'), false)->assertSee('Equipment types');
});
