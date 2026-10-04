<?php

use App\Models\Quartier;
use App\Models\AlerteMeteo;
use Database\Seeders\QuartierSeeder;
use App\Models\User;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('quartier factory creates a usable neighborhood', function () {
    $quartier = Quartier::factory()->create();

    expect($quartier->nom)->not->toBeEmpty()
        ->and($quartier->ville)->not->toBeEmpty()
        ->and($quartier->code_postal)->not->toBeEmpty();
});

test('quartier seeder creates the shared demo neighborhoods idempotently', function () {
    $this->seed(QuartierSeeder::class);
    $this->seed(QuartierSeeder::class);

    expect(Quartier::count())->toBe(8)
        ->and(Quartier::where('nom', 'La Marsa')->where('ville', 'Tunis')->exists())->toBeTrue();
});

test('admin can manage neighborhoods from the themed back office', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($admin)->get(route('admin.quartiers.index'))->assertOk()->assertSee('Neighborhoods');
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Ariana Centre', 'ville' => 'Ariana', 'code_postal' => '2080',
    ])->assertRedirect(route('admin.quartiers.index'));

    $quartier = Quartier::where('nom', 'Ariana Centre')->firstOrFail();
    $this->get(route('admin.quartiers.edit', $quartier))->assertOk()->assertSee('Ariana Centre');
    $this->put(route('admin.quartiers.update', $quartier), [
        'nom' => 'Ariana Nord', 'ville' => 'Ariana', 'code_postal' => '2080',
    ])->assertRedirect(route('admin.quartiers.index'));
    expect($quartier->fresh()->nom)->toBe('Ariana Nord');

    $this->delete(route('admin.quartiers.destroy', $quartier))->assertRedirect(route('admin.quartiers.index'));
    $this->assertDatabaseMissing('quartiers', ['id' => $quartier->id]);
});

test('admin can see a neighborhood alert count and its related alerts', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create(['nom' => 'Relation area']);
    $alerte = AlerteMeteo::factory()->for($quartier)->create(['titre' => 'Relation heat alert']);

    $this->actingAs($admin)->get(route('admin.quartiers.index'))
        ->assertOk()
        ->assertSee('Relation area')
        ->assertSee('1');
    $this->get(route('admin.quartiers.show', $quartier))
        ->assertOk()
        ->assertSee('Relation heat alert')
        ->assertSee(route('admin.alertes-meteo.show', $alerte));
});

test('guest is redirected from neighborhood pages', function () {
    $quartier = Quartier::factory()->create();

    $this->get(route('admin.quartiers.index'))->assertRedirect(route('login'));
    $this->get(route('admin.quartiers.show', $quartier))->assertRedirect(route('login'));
});

test('neighborhood list shows an empty state and handles an unknown filter', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($admin)->get(route('admin.quartiers.index'))
        ->assertOk()
        ->assertSee('No neighborhoods found');
    $this->get(route('admin.quartiers.index', ['q' => 'does-not-exist']))
        ->assertOk()
        ->assertSee('No neighborhoods found');
});

test('deleting a neighborhood cascades to its weather alerts', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create();
    $alerte = AlerteMeteo::factory()->for($quartier)->create();

    $this->actingAs($admin)->delete(route('admin.quartiers.destroy', $quartier))
        ->assertRedirect(route('admin.quartiers.index'));
    $this->assertDatabaseMissing('quartiers', ['id' => $quartier->id]);
    $this->assertDatabaseMissing('alerte_meteos', ['id' => $alerte->id]);
});
