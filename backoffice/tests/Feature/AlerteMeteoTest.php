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

