<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\User;

// Module 5 lists filter automatically (no Filter button) and show readable values, not database IDs.
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->type = fn (string $name): TypeEquipement => TypeEquipement::where('name', $name)->firstOrFail();
});

test('equipment filter form is auto-submitting and keeps the selected values', function () {
    SensitiveEquipment::factory()->ofType(($this->type)('Fan'))->create();
    $fan = ($this->type)('Fan');

    $response = $this->actingAs($this->admin)->get(route('admin.equipment.index', ['type' => $fan->id, 'risk' => 'medium', 'heat' => '1', 'outage' => '1', 'q' => 'fan']))->assertOk();

    $response->assertSee('data-auto-filter', false)
        ->assertSee('value="'.$fan->id.'" selected', false)
        ->assertSee('value="medium" selected', false)
        ->assertSee('name="heat" value="1" checked', false)
        ->assertSee('name="outage" value="1" checked', false)
        ->assertSee('value="fan"', false)
        ->assertSee('Reset filters');
});

test('the Filter button only exists as a no-JavaScript fallback and Reset goes to the clean URL', function () {
    $this->actingAs($this->admin);

    foreach (['admin.equipment.index', 'admin.profiles.index', 'admin.type-equipements.index'] as $route) {
        $html = $this->get(route($route, ['q' => 'x']))->assertOk()->getContent();

        expect($html)->toContain('data-auto-filter')
            ->and($html)->toMatch('/<noscript><button[^>]*>Apply filters<\/button><\/noscript>/')
            ->and($html)->not->toMatch('/<button class="ha-btn ha-btn--secondary"><svg/') // no visible Filter button
            ->and($html)->toContain('href="'.route($route).'"'); // Reset filters: no empty parameters

        expect($this->get(route($route))->getContent())->not->toContain('Reset filters');
    }
});

test('the auto-filter script debounces search and applies selects and checkboxes on change', function () {
    $js = file_get_contents(base_path('resources/js/auto-filter.js'));
    $app = file_get_contents(base_path('resources/js/app.js'));

    expect($app)->toContain("import './auto-filter'")
        ->and($js)->toContain('DEBOUNCE_MS = 400')
        ->and($js)->toContain("input[type=\"search\"]")
        ->and($js)->toContain('select, input[type="checkbox"], input[type="radio"]')
        ->and($js)->toContain("value.trim() !== ''"); // empty parameters are dropped
});

test('equipment filters combine and survive pagination', function () {
    $profile = Profile::factory()->create();
    SensitiveEquipment::factory()->count(12)->for($profile)->ofType(($this->type)('Refrigerator'))->create();
    SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Fan'))->create(['name' => 'Desk fan']);

    $query = ['risk' => 'high', 'heat' => '1', 'outage' => '1', 'type' => ($this->type)('Refrigerator')->id, 'q' => $profile->user->name];
    $response = $this->actingAs($this->admin)->get(route('admin.equipment.index', $query))->assertOk()->assertDontSee('Desk fan');

    expect($response->viewData('equipment')->total())->toBe(12);
    $next = $response->viewData('equipment')->appends($query)->nextPageUrl();
    foreach (['risk=high', 'heat=1', 'outage=1', 'type='.$query['type'], 'q='.rawurlencode($query['q'])] as $part) {
        expect($next)->toContain($part);
    }
    $this->get($next)->assertOk()->assertSee('value="high" selected', false);
});

test('the equipment list shows resident, type and risk by name, never ids', function () {
    $profile = Profile::factory()->create();
    $item = SensitiveEquipment::factory()->for($profile)->ofType(($this->type)('Medical equipment'))->create(['name' => 'Home respirator']);

    $this->actingAs($this->admin)->get(route('admin.equipment.index'))->assertOk()
        ->assertSee('Home respirator')->assertSee($profile->user->name)->assertSee('Medical equipment')->assertSee('CRITICAL')
        ->assertDontSee('Profile ID')->assertDontSee('Type ID')->assertDontSee('Equipment ID');

    foreach (['admin.equipment.show' => $item, 'admin.type-equipements.show' => $item->typeEquipement, 'admin.profiles.show' => $profile] as $route => $model) {
        $this->get(route($route, $model))->assertOk()
            ->assertDontSee('Profile ID')->assertDontSee('User ID')->assertDontSee('Type ID')->assertDontSee('Equipment ID')
            ->assertSee('Last updated');
    }
});

test('dashboard shows the real number of equipment types', function () {
    TypeEquipement::factory()->create(['name' => 'Wine cellar']);

    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
        ->assertViewHas('typesCount', 8)->assertSee('Equipment types');
});

test('risk badge colour comes from one mapping', function () {
    expect(TypeEquipement::riskVariant('critical'))->toBe('critical')
        ->and(TypeEquipement::riskVariant('high'))->toBe('danger')
        ->and(TypeEquipement::riskVariant('medium'))->toBe('warning')
        ->and(TypeEquipement::riskVariant('low'))->toBe('cool')
        ->and(TypeEquipement::riskVariant('unknown'))->toBe('neutral');
});
