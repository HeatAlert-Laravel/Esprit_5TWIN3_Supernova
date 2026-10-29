<?php

use App\Models\Quartier;
use App\Models\User;
use App\Support\HeatLevel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
});

function openMeteoPayload(array $maxTemperatures = [24.0, 38.2, 42.7]): array
{
    return ['daily' => [
        'time' => ['2026-11-01', '2026-11-02', '2026-11-03'],
        'temperature_2m_max' => $maxTemperatures,
        'temperature_2m_min' => [18.0, 27.5, 30.1],
        'apparent_temperature_max' => [24.5, 41.0, 46.2],
        'uv_index_max' => [4.0, 8.6, 10.9],
        'wind_speed_10m_max' => [12.3, 20.0, 31.4],
        'relative_humidity_2m_mean' => [70, 40, 25],
    ]];
}

test('heat levels follow the temperature thresholds', function (float $temperature, string $level) {
    expect(HeatLevel::forTemperature($temperature))->toBe($level);
})->with([
    [20.0, 'vert'], [32.9, 'vert'], [33.0, 'jaune'], [36.9, 'jaune'],
    [37.0, 'orange'], [40.9, 'orange'], [41.0, 'rouge'], [47.5, 'rouge'],
]);

test('only orange and red levels flag an outage risk', function () {
    expect(HeatLevel::risksOutage('vert'))->toBeFalse()
        ->and(HeatLevel::risksOutage('jaune'))->toBeFalse()
        ->and(HeatLevel::risksOutage('orange'))->toBeTrue()
        ->and(HeatLevel::risksOutage('rouge'))->toBeTrue();
});

test('admin gets the forecast of a neighborhood with the suggested level', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteoPayload())]);
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create(['latitude' => 36.8782, 'longitude' => 10.3247]);

    $response = $this->actingAs($admin)->getJson(route('admin.quartiers.forecast', $quartier))->assertOk();

    expect($response->json('source'))->toBe('Open-Meteo')
        ->and($response->json('days'))->toHaveCount(3)
        ->and($response->json('days.0.niveau'))->toBe('vert')
        ->and($response->json('days.1.niveau'))->toBe('orange')
        ->and($response->json('days.2.niveau'))->toBe('rouge')
        ->and($response->json('days.2.risque_coupure'))->toBeTrue()
        ->and($response->json('days.2.temperature_ressentie'))->toBe(46.2)
        ->and($response->json('days.1.humidite'))->toBe(40);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'latitude=36.8782') && str_contains($request->url(), 'longitude=10.3247'));
});

test('the forecast is cached between requests', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteoPayload())]);
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create(['latitude' => 36.8, 'longitude' => 10.1]);

    $this->actingAs($admin)->getJson(route('admin.quartiers.forecast', $quartier))->assertOk();
    $this->getJson(route('admin.quartiers.forecast', $quartier))->assertOk();

    Http::assertSentCount(1);
});

test('a neighborhood without coordinates cannot be forecast', function () {
    Http::fake();
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create(['latitude' => null, 'longitude' => null]);

    $this->actingAs($admin)->getJson(route('admin.quartiers.forecast', $quartier))
        ->assertStatus(422)->assertJsonPath('message', 'This neighborhood has no coordinates yet.');
    Http::assertNothingSent();
});

test('an unavailable weather service gives a clear message', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(['error' => true], 500)]);
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create(['latitude' => 36.8, 'longitude' => 10.1]);

    $this->actingAs($admin)->getJson(route('admin.quartiers.forecast', $quartier))
        ->assertStatus(503)->assertJsonPath('message', 'The weather service is unavailable. Please try again later.');
});

test('the forecast endpoint is reserved to administrators', function () {
    Http::fake();
    $quartier = Quartier::factory()->create(['latitude' => 36.8, 'longitude' => 10.1]);

    $this->getJson(route('admin.quartiers.forecast', $quartier))->assertUnauthorized();
    $this->actingAs(User::factory()->create(['role' => 'USER']))
        ->getJson(route('admin.quartiers.forecast', $quartier))->assertForbidden();
    Http::assertNothingSent();
});

test('the alert form offers the live forecast panel', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($admin)->get(route('admin.alertes-meteo.create'))
        ->assertOk()->assertSee('Live forecast (Open-Meteo)')->assertSee('data-weather-forecast', false);
});
