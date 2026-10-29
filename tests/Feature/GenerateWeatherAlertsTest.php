<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
});

/** Fakes Open-Meteo with one daily maximum per day starting today. */
function fakeMaxima(array $maxima): void
{
    // A fresh factory: a second Http::fake() would not replace the first stub.
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::fake(['api.open-meteo.com/*' => Http::response(['daily' => [
        'time' => collect($maxima)->keys()->map(fn ($i) => now()->addDays($i)->toDateString())->all(),
        'temperature_2m_max' => $maxima,
        'temperature_2m_min' => array_map(fn ($value) => $value - 8, $maxima),
        'apparent_temperature_max' => array_map(fn ($value) => $value + 2, $maxima),
        'uv_index_max' => array_fill(0, count($maxima), 8.0),
        'wind_speed_10m_max' => array_fill(0, count($maxima), 20.0),
        'relative_humidity_2m_mean' => array_fill(0, count($maxima), 40),
    ]])]);
}

function located(array $attributes = []): Quartier
{
    return Quartier::factory()->create($attributes + ['latitude' => 36.85, 'longitude' => 10.32]);
}

test('it publishes one automatic alert per day above green and none for green days', function () {
    fakeMaxima([25.0, 38.0, 42.0, 30.0]);
    $quartier = located(['nom' => 'La Marsa']);

    $this->artisan('alerts:generate', ['--days' => 4])->assertSuccessful();

    $alertes = AlerteMeteo::orderBy('date_debut')->get();
    expect($alertes)->toHaveCount(2)
        ->and($alertes->pluck('niveau')->all())->toBe(['orange', 'rouge'])
        ->and($alertes->every(fn ($alerte) => $alerte->publiee && $alerte->generee_auto && $alerte->quartier_id === $quartier->id))->toBeTrue()
        ->and($alertes[1]->titre)->toBe('Red heat alert - La Marsa')
        ->and($alertes[1]->type)->toBe('canicule')
        ->and($alertes[0]->type)->toBe('vague_de_chaleur')
        ->and($alertes[1]->risque_coupure)->toBeTrue()
        ->and($alertes[0]->source)->toBe('Open-Meteo (auto)')
        ->and($alertes[1]->date_debut->toDateString())->toBe($alertes[1]->date_fin->toDateString())
        ->and($alertes[1]->temperature_ressentie)->toBe(44.0);
});

test('running it twice never duplicates alerts', function () {
    fakeMaxima([38.0, 42.0]);
    located();

    $this->artisan('alerts:generate', ['--days' => 2])->assertSuccessful();
    $this->artisan('alerts:generate', ['--days' => 2])->assertSuccessful();

    expect(AlerteMeteo::count())->toBe(2);
});

test('a changed forecast updates the same alert', function () {
    $quartier = located();
    fakeMaxima([38.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();
    $id = AlerteMeteo::firstOrFail()->id;

    Cache::flush();
    fakeMaxima([43.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();

    $alerte = AlerteMeteo::firstOrFail();
    expect(AlerteMeteo::count())->toBe(1)
        ->and($alerte->id)->toBe($id)
        ->and($alerte->niveau)->toBe('rouge')
        ->and($alerte->temperature_max)->toBe(43.0)
        ->and($alerte->titre)->toContain('Red');
});

test('--draft creates unpublished alerts', function () {
    fakeMaxima([39.0]);
    located();

    $this->artisan('alerts:generate', ['--days' => 1, '--draft' => true])->assertSuccessful();

    expect(AlerteMeteo::firstOrFail()->publiee)->toBeFalse();
});

test('--dry-run shows the plan and writes nothing', function () {
    fakeMaxima([39.0, 42.0]);
    located();

    $this->artisan('alerts:generate', ['--days' => 2, '--dry-run' => true])
        ->expectsOutputToContain('2 created')->assertSuccessful();

    expect(AlerteMeteo::count())->toBe(0);
});

test('a day already covered by a manual alert is skipped', function () {
    fakeMaxima([39.0]);
    $quartier = located();
    AlerteMeteo::factory()->for($quartier)->create([
        'titre' => 'Manual alert', 'generee_auto' => false,
        'date_debut' => now()->subDay()->toDateString(), 'date_fin' => now()->addDay()->toDateString(),
    ]);

    $this->artisan('alerts:generate', ['--days' => 1])->expectsOutputToContain('1 skipped')->assertSuccessful();

    expect(AlerteMeteo::count())->toBe(1)->and(AlerteMeteo::first()->titre)->toBe('Manual alert');
});

test('an automatic alert unpublished by an administrator is not published again', function () {
    $quartier = located();
    fakeMaxima([39.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();
    AlerteMeteo::firstOrFail()->update(['publiee' => false]);

    Cache::flush();
    fakeMaxima([40.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();

    expect(AlerteMeteo::firstOrFail()->publiee)->toBeFalse()->and(AlerteMeteo::count())->toBe(1);
});

test('an automatic alert is withdrawn when its day turns green', function () {
    located();
    fakeMaxima([40.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();

    Cache::flush();
    fakeMaxima([27.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->expectsOutputToContain('1 withdrawn')->assertSuccessful();

    $alerte = AlerteMeteo::firstOrFail();
    expect($alerte->publiee)->toBeFalse()->and(AlerteMeteo::count())->toBe(1);
});

test('--simulate-temperature forces the level and marks the source as a simulation', function () {
    fakeMaxima([22.0, 21.0]);
    located();

    $this->artisan('alerts:generate', ['--days' => 2, '--simulate-temperature' => 42])->assertSuccessful();

    $alertes = AlerteMeteo::all();
    expect($alertes)->toHaveCount(2)
        ->and($alertes->pluck('niveau')->unique()->all())->toBe(['rouge'])
        ->and($alertes->first()->source)->toBe('Simulation (auto)')
        ->and($alertes->first()->temperature_min)->toBe(33.0);
});

test('invalid options are rejected', function () {
    Http::fake();

    $this->artisan('alerts:generate', ['--days' => 40])->assertExitCode(2);
    $this->artisan('alerts:generate', ['--simulate-temperature' => 'hot'])->assertExitCode(2);
    $this->artisan('alerts:generate', ['--quartier' => 'abc'])->assertExitCode(2);
    Http::assertNothingSent();
});

test('neighborhoods without coordinates are ignored and a failing one does not stop the others', function () {
    Quartier::factory()->create(['nom' => 'No coordinates', 'latitude' => null, 'longitude' => null]);
    located(['nom' => 'Down', 'ville' => 'Aaa']);
    located(['nom' => 'Up', 'ville' => 'Zzz']);

    $maxima = [40.0];
    Http::fake(['api.open-meteo.com/*' => Http::sequence()
        ->push([], 500)
        ->push(['daily' => [
            'time' => [now()->toDateString()], 'temperature_2m_max' => $maxima, 'temperature_2m_min' => [30.0],
            'apparent_temperature_max' => [43.0], 'uv_index_max' => [8.0], 'wind_speed_10m_max' => [20.0], 'relative_humidity_2m_mean' => [40],
        ]])]);

    $this->artisan('alerts:generate', ['--days' => 1])->expectsOutputToContain('Down: The weather service is unavailable')->assertSuccessful();

    expect(AlerteMeteo::count())->toBe(1)->and(AlerteMeteo::first()->quartier->nom)->toBe('Up');
});

test('the command fails when no neighborhood can be read at all', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
    located();

    $this->artisan('alerts:generate', ['--days' => 1])->assertFailed();
});

test('the command is scheduled every six hours', function () {
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($event) => str_contains($event->command, 'alerts:generate'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 */6 * * *');
});

test('the admin list marks automatic alerts and can filter by origin', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    AlerteMeteo::factory()->create(['titre' => 'Robot alert', 'generee_auto' => true]);
    AlerteMeteo::factory()->create(['titre' => 'Human alert', 'generee_auto' => false]);

    $this->actingAs($admin)->get(route('admin.alertes-meteo.index'))->assertOk()
        ->assertSee('Robot alert')->assertSee('Human alert')->assertSee('Auto')->assertSee('1 automatic');
    $this->get(route('admin.alertes-meteo.index', ['origine' => 'auto']))->assertSee('Robot alert')->assertDontSee('Human alert');
    $this->get(route('admin.alertes-meteo.index', ['origine' => 'manual']))->assertSee('Human alert')->assertDontSee('Robot alert');
});

test('the admin detail and edit pages explain that an alert was generated automatically', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $alerte = AlerteMeteo::factory()->create(['generee_auto' => true, 'source' => 'Open-Meteo (auto)']);

    $this->actingAs($admin)->get(route('admin.alertes-meteo.show', $alerte))->assertOk()
        ->assertSee('Generated automatically')->assertSee('Automatic');
    $this->get(route('admin.alertes-meteo.edit', $alerte))->assertOk()->assertSee('turns this alert into a manual one');
    $this->get(route('admin.alertes-meteo.show', AlerteMeteo::factory()->create(['generee_auto' => false])))->assertDontSee('Generated automatically');
});

test('editing an automatic alert makes it manual so it is never overwritten', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = located();
    fakeMaxima([39.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();
    $alerte = AlerteMeteo::firstOrFail();

    $this->actingAs($admin)->put(route('admin.alertes-meteo.update', $alerte), [
        'quartier_id' => $quartier->id, 'titre' => 'Edited by an administrator', 'niveau' => 'orange', 'temperature_max' => '39',
        'date_debut' => $alerte->date_debut->toDateString(), 'date_fin' => $alerte->date_fin->toDateString(), 'publiee' => '1',
    ])->assertRedirect()->assertSessionHas('status', fn ($message) => str_contains($message, 'now managed manually'));

    expect($alerte->fresh()->generee_auto)->toBeFalse();

    Cache::flush();
    fakeMaxima([43.0]);
    $this->artisan('alerts:generate', ['--days' => 1])->expectsOutputToContain('1 skipped')->assertSuccessful();

    expect(AlerteMeteo::count())->toBe(1)->and($alerte->fresh()->titre)->toBe('Edited by an administrator')->and($alerte->fresh()->niveau)->toBe('orange');
});

test('generated alerts show up on the resident pages', function () {
    fakeMaxima([42.0]);
    located(['nom' => 'Carthage']);
    $this->artisan('alerts:generate', ['--days' => 1])->assertSuccessful();

    $this->get(route('weather-alerts'))->assertOk()->assertSee('Red heat alert - Carthage');
});
