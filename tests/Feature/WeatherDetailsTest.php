<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Models\User;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

function detailedAlertPayload(Quartier $quartier, array $overrides = []): array
{
    return $overrides + [
        'quartier_id' => $quartier->id, 'titre' => 'Heat warning', 'niveau' => 'orange',
        'temperature_max' => '39.5', 'date_debut' => '2026-10-03', 'date_fin' => '2026-10-05',
    ];
}

test('admin stores the detailed weather fields of an alert', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();

    $this->actingAs($admin)->post(route('admin.alertes-meteo.store'), detailedAlertPayload($quartier, [
        'type' => 'orage', 'temperature_min' => '27', 'temperature_ressentie' => '44',
        'humidite' => '30', 'indice_uv' => '10', 'vitesse_vent' => '25.5',
        'risque_coupure' => '1', 'source' => 'Manual',
    ]))->assertRedirect(route('admin.alertes-meteo.index'));

    $alerte = AlerteMeteo::firstOrFail();
    expect($alerte->type)->toBe('orage')
        ->and($alerte->temperature_min)->toBe(27.0)
        ->and($alerte->humidite)->toBe(30)
        ->and($alerte->indice_uv)->toBe(10)
        ->and($alerte->risque_coupure)->toBeTrue();
    $this->get(route('admin.alertes-meteo.show', $alerte))->assertOk()->assertSee('Storm')->assertSee('25.5 km/h');
});

test('weather details are optional and the type defaults to heatwave', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();

    $this->actingAs($admin)->post(route('admin.alertes-meteo.store'), detailedAlertPayload($quartier))
        ->assertRedirect(route('admin.alertes-meteo.index'));

    $alerte = AlerteMeteo::firstOrFail();
    expect($alerte->type)->toBe('canicule')->and($alerte->humidite)->toBeNull()->and($alerte->risque_coupure)->toBeFalse();
});

test('weather detail validation rejects impossible values', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();

    $this->actingAs($admin)->post(route('admin.alertes-meteo.store'), detailedAlertPayload($quartier, [
        'type' => 'meteorite', 'temperature_min' => '45', 'humidite' => '150', 'indice_uv' => '99', 'vitesse_vent' => '-3',
    ]))->assertSessionHasErrors(['type', 'temperature_min', 'humidite', 'indice_uv', 'vitesse_vent']);
    expect(AlerteMeteo::count())->toBe(0);
});

test('residents see the outage risk and the weather details on a published alert', function () {
    $quartier = Quartier::factory()->create();
    AlerteMeteo::factory()->create([
        'quartier_id' => $quartier->id, 'titre' => 'Grid stress', 'publiee' => true,
        'risque_coupure' => true, 'humidite' => 33, 'temperature_ressentie' => 45,
    ]);

    $this->get(route('weather-alerts'))->assertOk()->assertSee('Power outage risk')->assertSee('Humidity 33%');
});

test('admin stores the governorate and coordinates of a neighborhood', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($admin)->post(route('admin.quartiers.store'), [
        'nom' => 'Lac 2', 'ville' => 'Tunis', 'gouvernorat' => 'Tunis', 'code_postal' => '1053',
        'latitude' => '36.8419', 'longitude' => '10.2700',
    ])->assertRedirect(route('admin.quartiers.index'));

    $quartier = Quartier::where('nom', 'Lac 2')->firstOrFail();
    expect($quartier->latitude)->toBe(36.8419)->and($quartier->hasCoordinates())->toBeTrue();
});

test('coordinates must come as a pair and stay within valid ranges', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $base = ['nom' => 'Lac 2', 'ville' => 'Tunis', 'code_postal' => '1053'];

    $this->actingAs($admin)->post(route('admin.quartiers.store'), $base + ['latitude' => '36.8'])
        ->assertSessionHasErrors('longitude');
    $this->post(route('admin.quartiers.store'), $base + ['latitude' => '120', 'longitude' => '10'])
        ->assertSessionHasErrors('latitude');
    expect(Quartier::count())->toBe(0);
});
