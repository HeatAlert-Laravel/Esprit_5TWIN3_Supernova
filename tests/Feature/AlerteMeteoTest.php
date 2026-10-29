<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Models\User;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('admin can create, view, update and delete a weather alert', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();

    $this->actingAs($admin)->post(route('admin.alertes-meteo.store'), [
        'quartier_id' => $quartier->id, 'titre' => 'Heat warning', 'niveau' => 'orange',
        'temperature_max' => '39.5', 'date_debut' => '2026-10-03', 'date_fin' => '2026-10-05', 'publiee' => '1',
    ])->assertRedirect(route('admin.alertes-meteo.index'));

    $alerte = AlerteMeteo::firstOrFail();
    $this->get(route('admin.alertes-meteo.show', $alerte))->assertOk()->assertSee('Heat warning')->assertSee($quartier->nom);
    $this->put(route('admin.alertes-meteo.update', $alerte), [
        'quartier_id' => $quartier->id, 'titre' => 'Extreme heat warning', 'niveau' => 'rouge',
        'temperature_max' => '42', 'date_debut' => '2026-10-03', 'date_fin' => '2026-10-06', 'publiee' => '1',
    ])->assertRedirect(route('admin.alertes-meteo.show', $alerte));
    expect($alerte->fresh()->niveau)->toBe('rouge');

    $this->delete(route('admin.alertes-meteo.destroy', $alerte))->assertRedirect(route('admin.alertes-meteo.index'));
    $this->assertDatabaseMissing('alerte_meteos', ['id' => $alerte->id]);
});

test('weather alert period status distinguishes current upcoming and expired alerts', function () {
    $quartier = Quartier::factory()->create();

    $current = AlerteMeteo::factory()->for($quartier)->create([
        'niveau' => 'vert',
        'date_debut' => today()->subDay(), 'date_fin' => today()->addDay(),
    ]);
    $upcoming = AlerteMeteo::factory()->for($quartier)->create([
        'niveau' => 'jaune',
        'date_debut' => today()->addDay(), 'date_fin' => today()->addDays(2),
    ]);
    $expired = AlerteMeteo::factory()->for($quartier)->create([
        'date_debut' => today()->subDays(3), 'date_fin' => today()->subDay(),
    ]);

    expect($current->temporalStatus())->toBe('current')
        ->and($upcoming->temporalStatus())->toBe('upcoming')
        ->and($expired->temporalStatus())->toBe('expired')
        ->and($current->levelBadgeVariant())->toBe('level-green')
        ->and($upcoming->levelBadgeVariant())->toBe('level-yellow')
        ->and($expired->temporalStatusBadgeVariant())->toBe('neutral');
});

test('admin can publish and unpublish a weather alert', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();
    $alerte = AlerteMeteo::factory()->for($quartier)->create(['publiee' => false]);

    $this->actingAs($admin)->put(route('admin.alertes-meteo.update', $alerte), [
        'quartier_id' => $quartier->id, 'titre' => $alerte->titre, 'niveau' => $alerte->niveau,
        'temperature_max' => $alerte->temperature_max, 'date_debut' => $alerte->date_debut->toDateString(),
        'date_fin' => $alerte->date_fin->toDateString(), 'publiee' => '1',
    ])->assertRedirect();
    expect($alerte->fresh()->publiee)->toBeTrue();

    $this->put(route('admin.alertes-meteo.update', $alerte), [
        'quartier_id' => $quartier->id, 'titre' => $alerte->titre, 'niveau' => $alerte->niveau,
        'temperature_max' => $alerte->temperature_max, 'date_debut' => $alerte->date_debut->toDateString(),
        'date_fin' => $alerte->date_fin->toDateString(), 'publiee' => '0',
    ])->assertRedirect();
    expect($alerte->fresh()->publiee)->toBeFalse();
});

