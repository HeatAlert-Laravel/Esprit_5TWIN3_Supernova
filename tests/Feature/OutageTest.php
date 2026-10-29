<?php

use App\Models\Coupure;
use App\Models\Quartier;
use App\Models\Signalement;
use App\Models\User;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('public outages show only active records with neighborhood filtering and report counts', function () {
    $quartier = Quartier::factory()->create();
    $active = Coupure::factory()->for($quartier)->create(['statut' => 'active', 'description' => 'Visible incident']);
    Coupure::factory()->for($quartier)->create(['statut' => 'résolue', 'description' => 'Resolved incident']);
    Coupure::factory()->create(['statut' => 'active', 'description' => 'Other area incident']);
    Signalement::factory()->for($active)->create();
    $this->get(route('outages', ['quartier_id' => $quartier->id]))->assertOk()
        ->assertSee('Visible incident')->assertSee('Reports: 1')->assertDontSee('Resolved incident')->assertDontSee('Other area incident');
    $this->get(route('outages', ['quartier_id' => 999999]))->assertSessionHasErrors('quartier_id');
});

test('residents can report with or without an outage using their authenticated identity', function () {
    $user = User::factory()->create();
    $coupure = Coupure::factory()->create(['statut' => 'active']);
    $this->actingAs($user)->get(route('outages.report.create'))->assertOk()->assertDontSee('name="user_id"', false);
    foreach ([null, $coupure->id] as $coupureId) {
        $this->post(route('outages.report.store'), ['adresse' => '12 rue des Jardins', 'description' => 'Panne électrique', 'coupure_id' => $coupureId, 'user_id' => null, 'statut' => null])->assertRedirect(route('outages'));
        $this->assertDatabaseHas('signalements', ['user_id' => $user->id, 'coupure_id' => $coupureId, 'statut' => 'en attente']);
    }
});

test('reporting requires login and rejects forged identity, status and invalid data', function () {
    $this->get(route('outages.report.create'))->assertRedirect(route('login'));
    $this->post(route('outages.report.store'), [])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create());
    $this->post(route('outages.report.store'), [
        'adresse' => '12 rue des Jardins', 'description' => 'Panne', 'user_id' => User::factory()->create()->id, 'statut' => 'validé',
    ])->assertSessionHasErrors(['user_id', 'statut']);
    expect(Signalement::count())->toBe(0);
    $this->post(route('outages.report.store'), ['coupure_id' => 999999])->assertSessionHasErrors(['adresse', 'description', 'coupure_id']);
});
