<?php

use App\Models\AlerteMeteo;
use App\Models\Conseil;
use App\Models\Coupure;
use App\Models\PointFraicheur;
use App\Models\Quartier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
});

function detailAlert(Quartier $quartier, string $niveau, string $titre, int $startOffset = 0, int $endOffset = 1, bool $published = true): AlerteMeteo
{
    return AlerteMeteo::factory()->for($quartier)->create([
        'niveau' => $niveau, 'titre' => $titre, 'publiee' => $published,
        // The factory draws this at random: pin it so the tests are deterministic.
        'risque_coupure' => false,
        'date_debut' => now()->addDays($startOffset)->toDateString(),
        'date_fin' => now()->addDays($endOffset)->toDateString(),
    ]);
}

test('a published alert has its own detail page', function () {
    $quartier = Quartier::factory()->create(['nom' => 'Carthage']);
    $alerte = detailAlert($quartier, 'rouge', 'Extreme heat today');
    $alerte->update(['temperature_ressentie' => 46, 'humidite' => 28, 'indice_uv' => 11, 'vitesse_vent' => 30, 'risque_coupure' => true, 'source' => 'Open-Meteo (auto)']);

    $this->get(route('weather-alerts.show', $alerte))->assertOk()
        ->assertSee('Extreme heat today')->assertSee('Red level')->assertSee('Carthage')
        ->assertSee('Power outage risk')->assertSee('Feels like')->assertSee('46°C')->assertSee('Source: Open-Meteo (auto)');
});

test('a draft alert has no public detail page', function () {
    $draft = detailAlert(Quartier::factory()->create(), 'rouge', 'Hidden draft', published: false);

    $this->get(route('weather-alerts.show', $draft))->assertNotFound();
});

test('alert cards link to their detail page', function () {
    $alerte = detailAlert(Quartier::factory()->create(), 'orange', 'Clickable heat');

    $this->get(route('weather-alerts'))->assertSee(route('weather-alerts.show', $alerte), false);
});

test('the detail page suggests published advice matching the situation', function () {
    $alerte = detailAlert(Quartier::factory()->create(), 'orange', 'Heat');
    Conseil::factory()->published()->create(['titre' => 'Heat advice', 'situation' => 'heatwave']);
    Conseil::factory()->published()->create(['titre' => 'Both advice', 'situation' => 'both']);
    Conseil::factory()->published()->create(['titre' => 'Outage only advice', 'situation' => 'outage']);
    Conseil::factory()->create(['titre' => 'Draft heat advice', 'situation' => 'heatwave']);

    $this->get(route('weather-alerts.show', $alerte))->assertOk()
        ->assertSee('Heat advice')->assertSee('Both advice')
        ->assertDontSee('Outage only advice')->assertDontSee('Draft heat advice');
});

test('an outage risk adds power outage advice', function () {
    $alerte = detailAlert(Quartier::factory()->create(), 'rouge', 'Grid stress');
    $alerte->update(['risque_coupure' => true]);
    Conseil::factory()->published()->create(['titre' => 'Outage only advice', 'situation' => 'outage']);

    $this->get(route('weather-alerts.show', $alerte))->assertSee('Outage only advice');
});

test('the detail page lists active cooling points and outages of the same neighborhood only', function () {
    $quartier = Quartier::factory()->create();
    $other = Quartier::factory()->create();
    $alerte = detailAlert($quartier, 'rouge', 'Heat');
    PointFraicheur::factory()->create(['quartier_id' => $quartier->id, 'nom' => 'Public library']);
    PointFraicheur::factory()->inactif()->create(['quartier_id' => $quartier->id, 'nom' => 'Closed garden']);
    PointFraicheur::factory()->create(['quartier_id' => $other->id, 'nom' => 'Faraway pool']);
    Coupure::factory()->create(['quartier_id' => $quartier->id, 'statut' => 'active', 'type' => 'en cours', 'description' => 'Transformer failure']);
    Coupure::factory()->create(['quartier_id' => $quartier->id, 'statut' => 'résolue', 'description' => 'Resolved incident']);
    Coupure::factory()->create(['quartier_id' => $other->id, 'statut' => 'active', 'description' => 'Elsewhere incident']);

    $response = $this->get(route('weather-alerts.show', $alerte))->assertOk()
        ->assertSee('Public library')->assertDontSee('Closed garden')->assertDontSee('Faraway pool')
        ->assertSee('Transformer failure')->assertDontSee('Resolved incident')->assertDontSee('Elsewhere incident')
        ->assertSee(route('cooling-points', ['quartier_id' => $quartier->id]), false)
        ->assertSee(route('outages', ['quartier_id' => $quartier->id]), false);

    expect($response->viewData('coolingPointsCount'))->toBe(1)->and($response->viewData('outagesCount'))->toBe(1);
});

test('the detail page says so when nothing is available around the neighborhood', function () {
    $alerte = detailAlert(Quartier::factory()->create(), 'orange', 'Quiet heat');

    $this->get(route('weather-alerts.show', $alerte))->assertOk()
        ->assertSee('No advice has been published for this situation yet.')
        ->assertSee('No cooling point is listed for this neighborhood yet.')
        ->assertSee('No active outage is reported in this neighborhood.');
});

test('the detail page shows the other current or upcoming published alerts of the neighborhood', function () {
    $quartier = Quartier::factory()->create();
    $alerte = detailAlert($quartier, 'rouge', 'Main alert');
    detailAlert($quartier, 'jaune', 'Sibling now');
    detailAlert($quartier, 'orange', 'Sibling soon', 2, 3);
    detailAlert($quartier, 'rouge', 'Sibling ended', -9, -7);
    detailAlert($quartier, 'rouge', 'Sibling draft', published: false);

    $this->get(route('weather-alerts.show', $alerte))->assertOk()
        ->assertSee('Sibling now')->assertSee('Sibling soon')
        ->assertDontSee('Sibling ended')->assertDontSee('Sibling draft');
});

test('the neighborhood page links to cooling points, outages and advice', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
    $quartier = Quartier::factory()->create(['latitude' => 36.85, 'longitude' => 10.32]);
    PointFraicheur::factory()->count(2)->create(['quartier_id' => $quartier->id]);
    Coupure::factory()->create(['quartier_id' => $quartier->id, 'statut' => 'active']);

    $response = $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee(route('cooling-points', ['quartier_id' => $quartier->id]), false)
        ->assertSee(route('outages', ['quartier_id' => $quartier->id]), false)
        ->assertSee('cooling points to cool down')->assertSee('active outage');

    expect($response->viewData('coolingPointsCount'))->toBe(2)->and($response->viewData('outagesCount'))->toBe(1);
});

test('the cross-module links never change the other modules data', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
    $quartier = Quartier::factory()->create();
    $alerte = detailAlert($quartier, 'rouge', 'Heat');
    $coupure = Coupure::factory()->create(['quartier_id' => $quartier->id, 'statut' => 'active']);
    $point = PointFraicheur::factory()->create(['quartier_id' => $quartier->id]);
    $conseil = Conseil::factory()->published()->create(['situation' => 'heatwave']);
    $before = [$coupure->fresh()->toArray(), $point->fresh()->toArray(), $conseil->fresh()->toArray()];

    $this->get(route('weather-alerts.show', $alerte))->assertOk();
    $this->get(route('neighborhoods.show', $quartier))->assertOk();

    expect([$coupure->fresh()->toArray(), $point->fresh()->toArray(), $conseil->fresh()->toArray()])->toBe($before)
        ->and($quartier->coupures()->count())->toBe(1);
});
