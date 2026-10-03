<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\User;

// Display logic added by the Shade & Signal redesign. In-memory SQLite, additive migrations only.
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
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

test('dashboard shows real KPIs and a priority breakdown from existing values', function () {
    $complete = Profile::factory()->create();
    $incomplete = Profile::factory()->create(['address' => '']);
    User::factory()->create(['role' => 'USER']); // resident without a profile
    SensitiveEquipment::factory()->for($complete)->create(['priority_level' => 'high']);
    SensitiveEquipment::factory()->for($complete)->create(['priority_level' => 'low']);

    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('usersCount', 4)
        ->assertViewHas('residentsWithoutProfile', 1)
        ->assertViewHas('profilesCount', 2)
        ->assertViewHas('completeProfilesCount', 1)
        ->assertViewHas('incompleteProfilesCount', 1)
        ->assertViewHas('equipmentCount', 2)
        ->assertSee('1 without a profile')->assertSee('1 complete')->assertSee('1 incomplete')
        ->assertSee('1 high')->assertSee('0 medium')->assertSee('1 low')
        ->assertSee('Planned modules')->assertSee('Coming soon');
});

test('households to check first follows the documented rule and order', function () {
    $quiet = Profile::factory()->create(['has_fragile_person' => false]);
    SensitiveEquipment::factory()->for($quiet)->create(['priority_level' => 'low']); // not listed

    $highOnly = Profile::factory()->create();
    SensitiveEquipment::factory()->for($highOnly)->count(2)->create(['priority_level' => 'high']);

    $incomplete = Profile::factory()->create(['neighborhood' => '']);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    $listed = $response->viewData('checkFirst')->pluck('id')->all();

    expect($listed)->toBe([$incomplete->id, $highOnly->id]); // incomplete first, quiet household excluded
    $response->assertSee('Households to check first')->assertSee('Incomplete details')->assertSee('2 high-priority items');
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
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'Freezer', 'priority_level' => 'medium']);

    $this->actingAs($this->admin)->get(route('admin.profiles.show', $profile))
        ->assertOk()
        ->assertSeeInOrder(['Identity', 'Contact', 'Household'])
        ->assertSee('Not provided')->assertSee('Incomplete')
        ->assertSee('Freezer')->assertSee('Priority: Medium')
        ->assertSee('Record metadata')->assertSee('Delete profile');
});

test('equipment list filters on existing fields only', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'Oxygen unit', 'type' => 'medical', 'priority_level' => 'high']);
    SensitiveEquipment::factory()->for($profile)->create(['name' => 'Desk fan', 'type' => 'household', 'priority_level' => 'low']);

    $this->actingAs($this->admin);
    $this->get(route('admin.equipment.index', ['priority' => 'high']))->assertOk()->assertSee('Oxygen unit')->assertDontSee('Desk fan');
    $this->get(route('admin.equipment.index', ['type' => 'household']))->assertOk()->assertSee('Desk fan')->assertDontSee('Oxygen unit');
    $this->get(route('admin.equipment.index', ['q' => $profile->user->name]))->assertOk()->assertSee('Oxygen unit')->assertSee('Desk fan');
    $this->get(route('admin.equipment.index', ['priority' => 'urgent']))->assertOk()->assertSee('Oxygen unit'); // unknown value is ignored
    $this->get(route('admin.equipment.index', ['q' => 'zzz']))->assertOk()->assertSee('No equipment matches these filters');
});

test('equipment detail shows owner context and form renders priority radio cards with the existing values', function () {
    $profile = Profile::factory()->create(['neighborhood' => 'Carthage']);
    $item = SensitiveEquipment::factory()->for($profile)->create(['priority_level' => 'medium']);

    $this->actingAs($this->admin);
    $this->get(route('admin.equipment.show', $item))->assertOk()
        ->assertSee('Equipment information')->assertSee('Owner')->assertSee($profile->user->name)->assertSee($profile->user->email)
        ->assertSee(route('admin.profiles.show', $profile), false);

    $this->get(route('admin.equipment.edit', $item))->assertOk()
        ->assertSee('name="priority_level" value="low"', false)
        ->assertSee('name="priority_level" value="medium" checked', false)
        ->assertSee('name="priority_level" value="high"', false)
        ->assertDontSee('<select id="priority_level"', false);
});

test('admin shell shows real sidebar counts and planned modules as coming soon', function () {
    Profile::factory()->count(2)->create();
    $this->actingAs($this->admin)->get(route('admin.profiles.index'))->assertOk()
        ->assertSee('Residents')->assertSee('Modules')->assertSee('Alerts')->assertSee('Soon')
        ->assertSee('aria-current="page"', false)->assertSee('Log out');
});
