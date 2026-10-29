<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
});

function publishedAlert(Quartier $quartier, string $niveau, string $titre, int $startOffset = 0, int $endOffset = 1, bool $published = true): AlerteMeteo
{
    return AlerteMeteo::factory()->for($quartier)->create([
        'niveau' => $niveau, 'titre' => $titre, 'publiee' => $published,
        'date_debut' => now()->addDays($startOffset)->toDateString(),
        'date_fin' => now()->addDays($endOffset)->toDateString(),
    ]);
}

function fakeForecast(array $max = [24.0, 38.5, 42.0, 30.0, 28.0]): void
{
    Http::fake(['api.open-meteo.com/*' => Http::response(['daily' => [
        'time' => collect($max)->keys()->map(fn ($i) => now()->addDays($i)->toDateString())->all(),
        'temperature_2m_max' => $max,
        'temperature_2m_min' => array_map(fn ($value) => $value - 8, $max),
        'apparent_temperature_max' => array_map(fn ($value) => $value + 2, $max),
        'uv_index_max' => array_fill(0, count($max), 7.0),
        'wind_speed_10m_max' => array_fill(0, count($max), 15.0),
        'relative_humidity_2m_mean' => array_fill(0, count($max), 50),
    ]])]);
}

test('level counters count only published alerts active today', function () {
    $quartier = Quartier::factory()->create();
    publishedAlert($quartier, 'rouge', 'Red now');
    publishedAlert($quartier, 'rouge', 'Red now 2');
    publishedAlert($quartier, 'orange', 'Orange now');
    publishedAlert($quartier, 'rouge', 'Red draft', published: false);
    publishedAlert($quartier, 'jaune', 'Yellow upcoming', 3, 4);
    publishedAlert($quartier, 'vert', 'Green ended', -5, -3);

    $counters = $this->get(route('weather-alerts'))->assertOk()->viewData('activeByLevel');

    expect($counters->all())->toBe(['rouge' => 2, 'orange' => 1, 'jaune' => 0, 'vert' => 0]);
});

test('the level filter narrows the list but not the counters', function () {
    $quartier = Quartier::factory()->create();
    publishedAlert($quartier, 'rouge', 'Extreme heat');
    publishedAlert($quartier, 'jaune', 'Mild heat');

    $response = $this->get(route('weather-alerts', ['niveau' => 'rouge']))->assertOk()
        ->assertSee('Extreme heat')->assertDontSee('Mild heat');

    expect($response->viewData('activeByLevel')['jaune'])->toBe(1);
});

test('an unknown level filter is ignored', function () {
    publishedAlert(Quartier::factory()->create(), 'orange', 'Any heat');

    $this->get(route('weather-alerts', ['niveau' => 'purple']))->assertOk()->assertSee('Any heat');
});

test('text search matches the alert title, the neighborhood and the city', function () {
    $marsa = Quartier::factory()->create(['nom' => 'La Marsa', 'ville' => 'Tunis', 'gouvernorat' => 'Tunis']);
    $sfax = Quartier::factory()->create(['nom' => 'Sfax Centre', 'ville' => 'Sfax', 'gouvernorat' => 'Sfax']);
    publishedAlert($marsa, 'orange', 'Beach heat');
    publishedAlert($sfax, 'orange', 'Industrial heat');

    $this->get(route('weather-alerts', ['q' => 'Beach']))->assertSee('Beach heat')->assertDontSee('Industrial heat');
    $this->get(route('weather-alerts', ['q' => 'Sfax']))->assertSee('Industrial heat')->assertDontSee('Beach heat');
    $this->get(route('weather-alerts', ['q' => 'Tunis']))->assertSee('Beach heat')->assertDontSee('Industrial heat');
});

test('drafts never appear in the resident list even when searched', function () {
    publishedAlert(Quartier::factory()->create(), 'rouge', 'Secret draft', published: false);

    $this->get(route('weather-alerts', ['q' => 'Secret']))->assertOk()->assertDontSee('Secret draft');
});

test('the neighborhood page shows the highest active level and groups alerts by period', function () {
    fakeForecast();
    $quartier = Quartier::factory()->create(['nom' => 'Carthage', 'latitude' => 36.85, 'longitude' => 10.32]);
    publishedAlert($quartier, 'jaune', 'Yellow now');
    publishedAlert($quartier, 'rouge', 'Red now');
    publishedAlert($quartier, 'orange', 'Orange soon', 2, 3);
    publishedAlert($quartier, 'orange', 'Orange earlier', -9, -7);
    publishedAlert($quartier, 'rouge', 'Draft red', published: false);

    $response = $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee('Carthage')->assertSee('Yellow now')->assertSee('Red now')
        ->assertSee('Orange soon')->assertSee('Orange earlier')->assertDontSee('Draft red');

    expect($response->viewData('level'))->toBe('rouge')
        ->and($response->viewData('current'))->toHaveCount(2)
        ->and($response->viewData('upcoming'))->toHaveCount(1)
        ->and($response->viewData('past'))->toHaveCount(1);
});

test('a neighborhood without active alert is shown as green', function () {
    fakeForecast();
    $quartier = Quartier::factory()->create(['latitude' => 36.85, 'longitude' => 10.32]);

    $response = $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee('No heat alert is active for this neighborhood today.');

    expect($response->viewData('level'))->toBe('vert');
});

test('the neighborhood page shows the live forecast with an estimated level per day', function () {
    fakeForecast();
    $quartier = Quartier::factory()->create(['latitude' => 36.85, 'longitude' => 10.32]);

    $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee('Live weather')->assertSee('Forecast by Open-Meteo')
        ->assertSee('Orange')->assertSee('Red');
});

test('the neighborhood page still works when the weather service is down', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
    $quartier = Quartier::factory()->create(['latitude' => 36.85, 'longitude' => 10.32]);
    publishedAlert($quartier, 'orange', 'Still visible');

    $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee('Forecast unavailable')->assertSee('Still visible');
});

test('a neighborhood without coordinates explains why there is no forecast', function () {
    Http::fake();
    $quartier = Quartier::factory()->create(['latitude' => null, 'longitude' => null]);

    $this->get(route('neighborhoods.show', $quartier))->assertOk()->assertSee('has no coordinates');
    Http::assertNothingSent();
});

test('alert cards link to the neighborhood page', function () {
    $quartier = Quartier::factory()->create();
    publishedAlert($quartier, 'orange', 'Linked heat');

    $this->get(route('weather-alerts'))->assertSee(route('neighborhoods.show', $quartier), false);
});

test('the alert list is paginated and keeps the filters across pages', function () {
    $quartier = Quartier::factory()->create();
    foreach (range(1, 12) as $number) {
        publishedAlert($quartier, 'orange', "Heat number {$number}");
    }

    $first = $this->get(route('weather-alerts', ['niveau' => 'orange']))->assertOk();
    expect($first->viewData('alertes')->total())->toBe(12)
        ->and($first->viewData('alertes')->count())->toBe(9);
    $first->assertSee('niveau=orange', false)->assertSee('page=2', false);

    $second = $this->get(route('weather-alerts', ['niveau' => 'orange', 'page' => 2]))->assertOk();
    expect($second->viewData('alertes')->count())->toBe(3);
});

test('there is no pagination when everything fits on one page', function () {
    publishedAlert(Quartier::factory()->create(), 'rouge', 'Only alert');

    $this->get(route('weather-alerts'))->assertOk()->assertDontSee('page=2', false);
});

test('active alerts come first, then upcoming, then ended', function () {
    $quartier = Quartier::factory()->create();
    publishedAlert($quartier, 'rouge', 'Ended red', -9, -7);
    publishedAlert($quartier, 'jaune', 'Upcoming yellow', 3, 4);
    publishedAlert($quartier, 'vert', 'Active green');
    publishedAlert($quartier, 'orange', 'Active orange');

    $titles = $this->get(route('weather-alerts'))->viewData('alertes')->pluck('titre')->all();

    expect($titles)->toBe(['Active orange', 'Active green', 'Upcoming yellow', 'Ended red']);
});

test('period tabs narrow the list and show their own counts', function () {
    $quartier = Quartier::factory()->create();
    publishedAlert($quartier, 'rouge', 'Ended red', -9, -7);
    publishedAlert($quartier, 'jaune', 'Upcoming yellow', 3, 4);
    publishedAlert($quartier, 'orange', 'Active orange');

    $titles = fn (string $periode) => $this->get(route('weather-alerts', ['periode' => $periode]))->assertOk()->viewData('alertes')->pluck('titre')->all();

    expect($titles('upcoming'))->toBe(['Upcoming yellow'])
        ->and($titles('current'))->toBe(['Active orange'])
        ->and($titles('past'))->toBe(['Ended red'])
        ->and($titles('nonsense'))->toHaveCount(3);
    expect($this->get(route('weather-alerts', ['periode' => 'upcoming']))->viewData('periodCounts'))
        ->toBe(['' => 3, 'current' => 1, 'upcoming' => 1, 'past' => 1]);
});

function fullForecastPayload(): array
{
    $hours = collect(range(0, 47))->map(fn ($i) => now()->startOfDay()->addHours($i));

    return [
        'current' => [
            'time' => now()->format('Y-m-d\TH:00'), 'temperature_2m' => 31.4, 'apparent_temperature' => 34.0,
            'relative_humidity_2m' => 52, 'weather_code' => 2, 'wind_speed_10m' => 18.2, 'is_day' => 1, 'precipitation' => 0.0,
        ],
        'hourly' => [
            'time' => $hours->map->format('Y-m-d\TH:00')->all(),
            'temperature_2m' => $hours->map(fn ($h) => 25 + $h->hour % 12)->all(),
            'weather_code' => $hours->map(fn ($h) => $h->hour < 12 ? 0 : 61)->all(),
            'precipitation_probability' => $hours->map(fn ($h) => $h->hour < 12 ? 0 : 40)->all(),
            'is_day' => $hours->map(fn ($h) => $h->hour >= 6 && $h->hour < 19 ? 1 : 0)->all(),
        ],
        'daily' => [
            'time' => collect(range(0, 2))->map(fn ($i) => now()->addDays($i)->toDateString())->all(),
            'weather_code' => [2, 95, 0],
            'temperature_2m_max' => [31.0, 38.0, 42.0], 'temperature_2m_min' => [22.0, 26.0, 29.0],
            'apparent_temperature_max' => [34.0, 41.0, 46.0], 'uv_index_max' => [8.0, 9.0, 10.0],
            'wind_speed_10m_max' => [20.0, 25.0, 30.0], 'precipitation_probability_max' => [10, 65, 0],
            'sunrise' => array_fill(0, 3, now()->format('Y-m-d').'T06:41'), 'sunset' => array_fill(0, 3, now()->format('Y-m-d').'T17:23'),
            'relative_humidity_2m_mean' => [50, 60, 30],
        ],
    ];
}

test('the neighborhood page shows current conditions, hourly and daily forecasts like a weather app', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(fullForecastPayload())]);
    $quartier = Quartier::factory()->create(['latitude' => 36.85, 'longitude' => 10.32]);

    $response = $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee('Partly cloudy')->assertSee('Feels like 34°')->assertSee('Rain chance')
        ->assertSee('Sunrise')->assertSee('06:41')->assertSee('17:23')
        ->assertSee('Next 24 hours')->assertSee('3-day forecast')
        ->assertSee('Thunderstorm')->assertSee('Clear sky')->assertSee('65%');

    $weather = $response->viewData('weather');
    expect($weather['current']['temperature'])->toBe(31.4)
        ->and($weather['current']['condition']['kind'])->toBe('partly')
        ->and($weather['hourly'])->toHaveCount(24)
        ->and($weather['hourly'][0]['hour'])->toBe(now()->format('H').':00')
        ->and($weather['daily'])->toHaveCount(3)
        ->and($weather['daily'][2]['niveau'])->toBe('rouge');
});

test('weather codes map to a label and an icon kind', function (int $code, string $label, string $kind) {
    expect(App\Support\WeatherCode::describe($code))->toBe(['label' => $label, 'kind' => $kind]);
})->with([
    [0, 'Clear sky', 'clear'], [2, 'Partly cloudy', 'partly'], [3, 'Overcast', 'cloudy'], [45, 'Fog', 'fog'],
    [63, 'Rain', 'rain'], [81, 'Rain showers', 'rain'], [73, 'Snow', 'snow'], [95, 'Thunderstorm', 'thunder'], [999, 'Unknown', 'cloudy'],
]);
