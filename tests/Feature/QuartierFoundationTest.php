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

test('neighborhood form rejects bad postal codes and duplicate names in the same city', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    Quartier::factory()->create(['nom' => 'Centre Urbain Nord', 'ville' => 'Tunis', 'code_postal' => '1082']);

    // Postal code must be digits only, and exactly 4 of them.
    $this->actingAs($admin)->post(route('admin.quartiers.store'), [
        'nom' => 'Bab Souika', 'ville' => 'Tunis', 'code_postal' => 'ABCD',
    ])->assertSessionHasErrors('code_postal');
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Bab Souika', 'ville' => 'Tunis', 'code_postal' => '123',
    ])->assertSessionHasErrors('code_postal');
    // Tunisian postal codes are 4 digits long: a fifth digit is a typo, not a longer code.
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Bab Souika', 'ville' => 'Tunis', 'code_postal' => '10530',
    ])->assertSessionHasErrors('code_postal');
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Bab Souika', 'ville' => 'Tunis', 'code_postal' => '10 53',
    ])->assertSessionHasErrors('code_postal');
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Bab Souika', 'ville' => 'Tunis', 'code_postal' => '',
    ])->assertSessionHasErrors('code_postal');
    $this->assertDatabaseMissing('quartiers', ['nom' => 'Bab Souika']);

    // Four digits are accepted, even with a leading zero: the code stays a string.
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Bab Souika', 'ville' => 'Tunis', 'code_postal' => '0700',
    ])->assertRedirect(route('admin.quartiers.index'));
    expect(Quartier::where('nom', 'Bab Souika')->firstOrFail()->code_postal)->toBe('0700');

    // The name must be unique within the same city...
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Centre Urbain Nord', 'ville' => 'Tunis', 'code_postal' => '1082',
    ])->assertSessionHasErrors('nom');
    expect(Quartier::where('nom', 'Centre Urbain Nord')->count())->toBe(1);

    // ...but the same name is allowed in another city.
    $this->post(route('admin.quartiers.store'), [
        'nom' => 'Centre Urbain Nord', 'ville' => 'Sousse', 'code_postal' => '4000',
    ])->assertRedirect(route('admin.quartiers.index'));
    expect(Quartier::where('nom', 'Centre Urbain Nord')->count())->toBe(2);

    // Editing a neighborhood while keeping its own name must not trip the unique rule.
    $quartier = Quartier::where('ville', 'Tunis')->firstOrFail();
    $this->put(route('admin.quartiers.update', $quartier), [
        'nom' => 'Centre Urbain Nord', 'ville' => 'Tunis', 'code_postal' => '1082',
    ])->assertRedirect(route('admin.quartiers.index'));
    expect($quartier->fresh()->code_postal)->toBe('1082');
});

test('neighborhood validation runs on the server and reports errors inline, in English', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $quartier = Quartier::factory()->create(['nom' => 'Centre Ville', 'ville' => 'Tunis', 'code_postal' => '1082']);

    $this->actingAs($admin)->get(route('admin.quartiers.create'))
        ->assertOk()
        ->assertDontSee('pattern=', false)
        ->assertDontSee('inputmode=', false)
        ->assertDontSee('required', false)
        ->assertSee('Unique within the selected city.')
        ->assertSee('Numbers only');
    $this->get(route('admin.quartiers.edit', $quartier))
        ->assertOk()
        ->assertDontSee('pattern=', false)
        ->assertDontSee('required', false);

    $this->actingAs($admin)
        ->from(route('admin.quartiers.create'))
        ->followingRedirects()
        ->post(route('admin.quartiers.store'), ['nom' => 'Neo', 'ville' => 'Tunis', 'code_postal' => 'ABCD'])
        ->assertOk()
        ->assertSee('id="code_postal-error"', false)
        ->assertSee('The postal code field format is invalid.')
        ->assertDontSee('Please correct the highlighted fields.');
});

