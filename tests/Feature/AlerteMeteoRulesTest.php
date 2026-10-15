<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Models\User;
use Database\Seeders\AlerteMeteoSeeder;
use Database\Seeders\QuartierSeeder;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

function validAlert(Quartier $quartier, array $overrides = []): array
{
    return array_merge([
        'quartier_id' => $quartier->id,
        'titre' => 'Forte chaleur',
        'niveau' => 'orange',
        'temperature_max' => 39.5,
        'date_debut' => '2026-07-10',
        'date_fin' => '2026-07-12',
        'publiee' => 1,
    ], $overrides);
}

test('weather alert validation rejects bad level, bad dates, bad temperature and unknown neighborhood', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();

    $this->actingAs($admin)->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['niveau' => 'bleu']))->assertSessionHasErrors('niveau');
    $this->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['date_fin' => '2026-07-09']))->assertSessionHasErrors('date_fin');
    $this->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['temperature_max' => 99]))->assertSessionHasErrors('temperature_max');
    $this->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['quartier_id' => 9999]))->assertSessionHasErrors('quartier_id');
    $this->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['titre' => '']))->assertSessionHasErrors('titre');

    expect(AlerteMeteo::count())->toBe(0);
});

test('weather alert update validates and keeps the stored values on failure', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $alerte = AlerteMeteo::factory()->create(['niveau' => 'jaune']);

    $this->actingAs($admin)->put(route('admin.alertes-meteo.update', $alerte), validAlert($alerte->quartier, ['niveau' => 'noir']))->assertSessionHasErrors('niveau');

    expect($alerte->fresh()->niveau)->toBe('jaune');
});

test('a resident cannot reach weather alert management', function () {
    $user = User::factory()->create(['role' => 'USER']);
    $alerte = AlerteMeteo::factory()->create();

    $this->actingAs($user)->get(route('admin.alertes-meteo.index'))->assertForbidden();
    $this->delete(route('admin.alertes-meteo.destroy', $alerte))->assertForbidden();

    expect(AlerteMeteo::count())->toBe(1);
});

test('deleting a neighborhood removes its alerts and alerts belong to one neighborhood', function () {
    $quartier = Quartier::factory()->create();
    $alerte = AlerteMeteo::factory()->create(['quartier_id' => $quartier->id]);

    expect($alerte->quartier->is($quartier))->toBeTrue()
        ->and($quartier->alerteMeteos)->toHaveCount(1);

    $quartier->delete();

    expect(AlerteMeteo::count())->toBe(0);
});

test('admin index filters by neighborhood and paginates ten per page', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $other = Quartier::factory()->create(['nom' => 'Autre Quartier']);
    AlerteMeteo::factory()->count(11)->create(['quartier_id' => Quartier::factory()->create()->id]);
    AlerteMeteo::factory()->create(['quartier_id' => $other->id, 'titre' => 'Only Other Alert']);

    $this->actingAs($admin)->get(route('admin.alertes-meteo.index'))->assertOk()->assertSee('pagination', false);
    $this->get(route('admin.alertes-meteo.index', ['quartier_id' => $other->id]))->assertSee('Only Other Alert')->assertDontSee('Vigilance chaleur -');
});

test('alert seeder is idempotent and covers every level, draft and expired alerts', function () {
    $this->seed(QuartierSeeder::class);
    $this->seed(AlerteMeteoSeeder::class);
    $this->seed(AlerteMeteoSeeder::class);

    expect(AlerteMeteo::count())->toBe(16)
        ->and(AlerteMeteo::pluck('niveau')->unique()->sort()->values()->all())->toBe(['jaune', 'orange', 'rouge', 'vert'])
        ->and(AlerteMeteo::where('publiee', false)->exists())->toBeTrue()
        ->and(AlerteMeteo::whereDate('date_fin', '<', now())->exists())->toBeTrue();
});

test('weather alert validation runs on the server and reports errors inline, in English', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();
    $alerte = AlerteMeteo::factory()->create();

    $this->actingAs($admin)->get(route('admin.alertes-meteo.create'))
        ->assertOk()
        ->assertDontSee('min="-50"', false)
        ->assertDontSee('max="60"', false)
        ->assertDontSee('step="1"', false)
        ->assertDontSee('required', false)
        ->assertSee('step="any"', false)
        ->assertSee('Decimals are allowed, for example 32.5.')
        ->assertSee('Must be on or after the start date.');
    $this->get(route('admin.alertes-meteo.edit', $alerte))
        ->assertOk()
        ->assertDontSee('min="-50"', false)
        ->assertDontSee('step="1"', false)
        ->assertSee('step="any"', false)
        ->assertDontSee('required', false);

    $this->actingAs($admin)
        ->from(route('admin.alertes-meteo.create'))
        ->followingRedirects()
        ->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['temperature_max' => 99]))
        ->assertOk()
        ->assertSee('id="temperature_max-error"', false)
        ->assertSee('The maximum temperature field must be between -50 and 60.')
        ->assertDontSee('Please correct the highlighted fields.');
});

test('a decimal maximum temperature is accepted, kept and displayed without rounding', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.alertes-meteo.store'), validAlert($quartier, ['temperature_max' => 32.5]))
        ->assertRedirect(route('admin.alertes-meteo.index'));

    $alerte = AlerteMeteo::firstOrFail();

    expect($alerte->temperature_max)->toBe(32.5);

    $this->get(route('admin.alertes-meteo.show', $alerte))->assertOk()->assertSee('32.5 °C');
    $this->get(route('admin.alertes-meteo.index'))->assertOk()->assertSee('32.5 °C');
});
