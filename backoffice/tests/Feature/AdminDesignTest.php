<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\User;

// Display logic added by the Shade & Signal redesign. In-memory SQLite, additive migrations only.
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    // Canonical types (created by the migrations): Refrigerator high, Medical equipment critical, Fan medium, Aquarium medium.
    $this->type = fn (string $name): TypeEquipement => TypeEquipement::where('name', $name)->firstOrFail();
});

test('profile completeness is computed only from phone, address and neighborhood', function () {
    $complete = Profile::factory()->create();
    expect($complete->isComplete())->toBeTrue()->and($complete->completionPercent())->toBe(100);

    $blank = Profile::factory()->create(['phone' => '  ']);
    expect($blank->isComplete())->toBeFalse()
        ->and($blank->missingDetails())->toBe(['phone'])
        ->and($blank->completionPercent())->toBe(67);

    // has_fragile_person is a yes/no answer and never counts as missing.
    $noFlag = Profile::factory()->create(['has_fragile_person' => false]);
    expect($noFlag->isComplete())->toBeTrue();

    // The SQL scope agrees with the PHP rule.
    expect(Profile::incomplete()->pluck('id')->all())->toBe([$blank->id]);
});

test('dashboard shows real KPIs and a risk breakdown from equipment types', function () {
    $complete = Profile::factory()->create();
    $incomplete = Profile::factory()->create(['address' => '']);
    User::factory()->create(['role' => 'USER']); // resident without a profile
    SensitiveEquipment::factory()->for($complete)->ofType(($this->type)('Refrigerator'))->create(); // high, heat + outage
    SensitiveEquipment::factory()->for($complete)->ofType(($this->type)('Fan'))->create(); // medium, outage only

    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('usersCount', 4)
        ->assertViewHas('residentsWithoutProfile', 1)
        ->assertViewHas('profilesCount', 2)
        ->assertViewHas('completeProfilesCount', 1)
        ->assertViewHas('incompleteProfilesCount', 1)
        ->assertViewHas('equipmentStats', ['total' => 2, 'high_risk' => 1, 'heat' => 1, 'outage' => 2])
        ->assertSee('1 without a profile')->assertSee('1 complete')->assertSee('1 incomplete')
        ->assertSee('0 critical')->assertSee('1 high')->assertSee('1 medium')->assertSee('0 low')
        ->assertSee('Planned modules')->assertSee('Coming soon');
});

test('households to check first follows the documented rule and order', function () {
    $quiet = Profile::factory()->create(['has_fragile_person' => false]);
    SensitiveEquipment::factory()->for($quiet)->ofType(($this->type)('Fan'))->create(); // medium risk: not listed

    $highOnly = Profile::factory()->create();
    SensitiveEquipment::factory()->for($highOnly)->ofType(($this->type)('Medical equipment'))->create(); // critical
    SensitiveEquipment::factory()->for($highOnly)->ofType(($this->type)('Freezer'))->create(); // high

    $incomplete = Profile::factory()->create(['neighborhood' => '']);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    $listed = $response->viewData('checkFirst')->pluck('id')->all();

    expect($listed)->toBe([$incomplete->id, $highOnly->id]); // incomplete first, quiet household excluded
    $response->assertSee('Households to check first')->assertSee('Incomplete details')->assertSee('2 high-risk items');
});

test('dashboard shows friendly empty states without data', function () {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()->assertSee('Nothing to check right now')->assertSee('No equipment recorded yet');
});

test('profiles list filters by resident and neighborhood and shows empty states', function () {
    $amina = User::factory()->create(['name' => 'Amina Test', 'email' => 'amina.t@example.test']);
    $youssef = User::factory()->create(['name' => 'Youssef Test', 'email' => 'youssef.t@example.test']);
    Profile::factory()->for($amina)->create(['neighborhood' => 'Carthage']);
    Profile::factory()->for($youssef)->create(['neighborhood' => 'Le Bardo']);

    $this->actingAs($this->admin);
    $this->get(route('admin.profiles.index', ['q' => 'amina']))->assertOk()->assertSee('Amina Test')->assertDontSee('Youssef Test');
    $this->get(route('admin.profiles.index', ['q' => 'youssef.t@example']))->assertOk()->assertSee('Youssef Test')->assertDontSee('Amina Test');
    $this->get(route('admin.profiles.index', ['neighborhood' => 'Le Bardo']))->assertOk()->assertSee('Youssef Test')->assertDontSee('Amina Test');
    $this->get(route('admin.profiles.index', ['q' => 'nobody']))->assertOk()->assertSee('No profiles match these filters');
    $this->get(route('admin.profiles.index'))->assertOk()->assertSee('Complete')->assertSee('2 records');

    Profile::query()->delete();
    $this->get(route('admin.profiles.index'))->assertOk()->assertSee('No profiles yet');
});

test('profile detail shows grouped cards and Not provided for blank values', function () {
    $profile = Profile::factory()->create(['phone' => '']);
    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Aquarium'))->create(['name' => 'Tropical tank']);

    $this->actingAs($this->admin)->get(route('admin.profiles.show', $profile))
        ->assertOk()
        ->assertSeeInOrder(['Identity', 'Contact', 'Household'])
        ->assertSee('Not provided')->assertSee('Incomplete')
        ->assertSee('Tropical tank')->assertSee('Risk: MEDIUM')->assertSee('Aquarium')
        ->assertSee('Record metadata')->assertSee('Delete profile');
});

test('equipment list filters by type, risk and sensitivity and keeps filters while paginating', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Medical equipment'))->create(['name' => 'Oxygen unit']);
    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Fan'))->create(['name' => 'Desk fan']);
    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Aquarium'))->create(['name' => 'Fish tank']);

    $this->actingAs($this->admin);
    $this->get(route('admin.equipment.index', ['risk' => 'critical']))->assertOk()->assertSee('Oxygen unit')->assertDontSee('Desk fan')->assertDontSee('Fish tank');
    $this->get(route('admin.equipment.index', ['risk' => 'medium']))->assertOk()->assertSee('Desk fan')->assertSee('Fish tank')->assertDontSee('Oxygen unit');
    $this->get(route('admin.equipment.index', ['type' => ($this->type)('Fan')->id]))->assertOk()->assertSee('Desk fan')->assertDontSee('Oxygen unit');
    $this->get(route('admin.equipment.index', ['heat' => '1']))->assertOk()->assertSee('Oxygen unit')->assertSee('Fish tank')->assertDontSee('Desk fan');
    $this->get(route('admin.equipment.index', ['outage' => '1']))->assertOk()->assertSee('Oxygen unit')->assertSee('Desk fan')->assertSee('Fish tank');
    $this->get(route('admin.equipment.index', ['heat' => '1', 'risk' => 'medium']))->assertOk()->assertSee('Fish tank')->assertDontSee('Oxygen unit')->assertDontSee('Desk fan');
    $this->get(route('admin.equipment.index', ['q' => $profile->user->name]))->assertOk()->assertSee('Oxygen unit')->assertSee('Desk fan');
    $this->get(route('admin.equipment.index', ['risk' => 'urgent']))->assertOk()->assertSee('Oxygen unit'); // unknown value is ignored
    $this->get(route('admin.equipment.index', ['q' => 'zzz']))->assertOk()->assertSee('No equipment matches these filters')->assertSee('Reset filters');

    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Fan'))->count(11)->create();
    $this->get(route('admin.equipment.index', ['type' => ($this->type)('Fan')->id]))->assertOk()
        ->assertSee('type='.($this->type)('Fan')->id.'&amp;page=2', false);
});

test('equipment list shows type, sensitivities and risk badge from the type', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Medical equipment'))->create(['name' => 'Oxygen unit']);

    $this->actingAs($this->admin)->get(route('admin.equipment.index'))->assertOk()
        ->assertSeeInOrder(['Equipment', 'Resident', 'Type', 'Heat', 'Outage', 'Risk', 'Updated'])
        ->assertSee('Oxygen unit')->assertSee('Medical equipment')->assertSee('Heat-sensitive')->assertSee('Outage-sensitive')->assertSee('CRITICAL');
});

test('equipment detail shows owner and type context and the form has a type select', function () {
    $profile = Profile::factory()->create(['neighborhood' => 'Carthage']);
    $type = ($this->type)('Freezer');
    $item = SensitiveEquipment::factory()->for($profile)->ofType($type)->create();

    $this->actingAs($this->admin);
    $this->get(route('admin.equipment.show', $item))->assertOk()
        ->assertSee('Equipment information')->assertSee('Resident')->assertSee($profile->user->name)->assertSee($profile->user->email)
        ->assertSee(route('admin.profiles.show', $profile), false)
        ->assertSee(route('admin.type-equipements.show', $type), false)
        ->assertSee('Risk: HIGH')->assertSee('Preparedness')
        ->assertSee('Consider a backup power plan.');

    $this->get(route('admin.equipment.edit', $item))->assertOk()
        ->assertSee('<select id="type_equipement_id" name="type_equipement_id"', false)
        ->assertSee('value="'.$type->id.'" selected', false)
        ->assertDontSee('name="priority_level"', false)->assertDontSee('name="type"', false);
});

test('admin shell shows real sidebar counts and links to the implemented advice module', function () {
    Profile::factory()->count(2)->create();
    $this->actingAs($this->admin)->get(route('admin.profiles.index'))->assertOk()
        ->assertSee('Residents')->assertSee('Outages')->assertSee('Advice categories')
        ->assertSee(route('admin.conseils.index'), false)->assertSee(route('admin.categorie-conseils.index'), false)
        ->assertSee('aria-current="page"', false)->assertSee('Log out');
});
