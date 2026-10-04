<?php

use App\Models\Coupure;
use App\Models\Quartier;
use App\Models\Signalement;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->artisan('migrate', ['--force' => true]);
});

function outagePayload(Quartier $quartier): array
{
    return ['quartier_id' => $quartier->id, 'type' => 'en cours', 'statut' => 'active', 'date_debut' => '2026-10-04 10:00', 'date_fin_estimee' => '2026-10-04 12:00', 'description' => 'Incident réseau'];
}

test('admin can use both complete CRUDs and change report status', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    $resident = User::factory()->create();
    $quartier = Quartier::factory()->create();
    $payload = outagePayload($quartier);
    $this->get(route('admin.coupures.create'))->assertOk();
    $this->post(route('admin.coupures.store'), $payload)->assertRedirect(route('admin.coupures.index'));
    $coupure = Coupure::firstOrFail();
    foreach (['index', 'show', 'edit'] as $action) {
        $this->get(route('admin.coupures.'.$action, $action === 'index' ? [] : $coupure))->assertOk()->assertSee($quartier->nom);
    }
    $this->put(route('admin.coupures.update', $coupure), array_replace($payload, ['statut' => 'résolue']))->assertRedirect();
    expect($coupure->fresh()->statut)->toBe('résolue');

    $report = ['user_id' => $resident->id, 'coupure_id' => $coupure->id, 'adresse' => '12 rue des Jardins', 'description' => 'Sans courant', 'statut' => 'en attente'];
    $this->get(route('admin.signalements.create'))->assertOk();
    $this->post(route('admin.signalements.store'), $report)->assertRedirect(route('admin.signalements.index'));
    $signalement = Signalement::firstOrFail();
    foreach (['index', 'show', 'edit'] as $action) {
        $this->get(route('admin.signalements.'.$action, $action === 'index' ? [] : $signalement))->assertOk()->assertSee($resident->name);
    }
    $this->put(route('admin.signalements.update', $signalement), array_replace($report, ['coupure_id' => null, 'adresse' => '14 rue des Jardins']))->assertRedirect();
    expect($signalement->fresh()->coupure_id)->toBeNull();
    foreach (['validé', 'rejeté', 'en attente'] as $statut) {
        $this->patch(route('admin.signalements.statut', $signalement), ['statut' => $statut, 'user_id' => 999999])->assertRedirect();
        expect($signalement->fresh()->statut)->toBe($statut);
        expect($signalement->fresh()->user_id)->toBe($resident->id);
    }
    $signalement->update(['coupure_id' => $coupure->id]);
    $this->delete(route('admin.coupures.destroy', $coupure))->assertRedirect();
    expect($signalement->fresh()->coupure_id)->toBeNull();
    $this->delete(route('admin.signalements.destroy', $signalement))->assertRedirect();
    $this->assertDatabaseMissing('signalements', ['id' => $signalement->id]);
});

test('requests reject invalid foreign keys, states and dates', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    $payload = outagePayload(Quartier::factory()->create());
    $this->post(route('admin.coupures.store'), array_replace($payload, [
        'quartier_id' => 999999, 'type' => 'autre', 'statut' => 'autre', 'date_fin_estimee' => '2026-10-03 10:00',
    ]))->assertSessionHasErrors(['quartier_id', 'type', 'statut', 'date_fin_estimee']);
    $this->post(route('admin.signalements.store'), [
        'user_id' => 999999, 'coupure_id' => 999999, 'adresse' => '', 'description' => '', 'statut' => 'autre',
    ])->assertSessionHasErrors(['user_id', 'coupure_id', 'adresse', 'description', 'statut']);
    $signalement = Signalement::factory()->sansCoupure()->create();
    $this->patch(route('admin.signalements.statut', $signalement), ['statut' => 'autre'])->assertSessionHasErrors('statut');
});

test('admin pages require authentication and admin role', function () {
    $this->get(route('admin.coupures.index'))->assertRedirect(route('login'));
    $this->get(route('admin.signalements.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'USER']));
    $this->get(route('admin.coupures.index'))->assertForbidden();
    $this->get(route('admin.signalements.index'))->assertForbidden();
    $this->post(route('admin.coupures.store'), [])->assertForbidden();
    $this->post(route('admin.signalements.store'), [])->assertForbidden();
});

test('relations and database foreign keys preserve reports when an outage is deleted', function () {
    $coupure = Coupure::factory()->create();
    $report = Signalement::factory()->for($coupure)->create();
    expect($coupure->quartier)->toBeInstanceOf(Quartier::class);
    expect($report->coupure->is($coupure))->toBeTrue();
    expect($coupure->signalements->first()->is($report))->toBeTrue();
    $coupure->delete();
    expect($report->fresh()->coupure_id)->toBeNull();
    $report->user->delete();
    $this->assertDatabaseMissing('signalements', ['id' => $report->id]);
});

test('demo seeders are repeatable and keep existing records', function () {
    $quartier = Quartier::factory()->create();
    User::factory()->create(['role' => 'USER']);
    $this->seed([\Database\Seeders\CoupureSeeder::class, \Database\Seeders\SignalementSeeder::class]);
    $counts = [Coupure::count(), Signalement::count()];
    $this->seed([\Database\Seeders\CoupureSeeder::class, \Database\Seeders\SignalementSeeder::class]);
    expect([Coupure::count(), Signalement::count()])->toBe($counts);
    expect(Signalement::whereNull('coupure_id')->exists())->toBeTrue();
    expect(Signalement::whereNotNull('coupure_id')->exists())->toBeTrue();
    expect($quartier->fresh())->not->toBeNull();
});

test('deleting a neighborhood with outages is blocked with a readable message', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    $coupure = Coupure::factory()->create();
    $this->delete(route('admin.quartiers.destroy', $coupure->quartier))
        ->assertRedirect(route('admin.quartiers.index'))->assertSessionHas('error');
    expect($coupure->fresh())->not->toBeNull();
});
