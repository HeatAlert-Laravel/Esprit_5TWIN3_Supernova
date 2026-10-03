<?php

use App\Models\Quartier;
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
