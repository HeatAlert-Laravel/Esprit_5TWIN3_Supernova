<?php

use App\Models\PointFraicheur;
use App\Models\Quartier;
use App\Models\TypePoint;
use App\Models\User;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('residents see only active cooling points matching their filters', function () {
    $point = PointFraicheur::factory()->create(['nom' => 'Accessible library', 'accessible_pmr' => true]);
    PointFraicheur::factory()->inactif()->create(['nom' => 'Hidden library']);
    PointFraicheur::factory()->create(['nom' => 'Another location']);

    $this->get(route('cooling-points', [
        'quartier_id' => $point->quartier_id, 'type_id' => $point->type_point_id, 'pmr' => '1',
    ]))->assertOk()->assertSee('Accessible library')->assertDontSee('Hidden library')->assertDontSee('Another location');
    expect($point->quartier)->toBeInstanceOf(Quartier::class)
        ->and($point->typePoint->pointFraicheurs->modelKeys())->toContain($point->id);
});

test('cooling point administration rejects residents including direct mutation requests', function () {
    $point = PointFraicheur::factory()->create();
    $this->get(route('admin.point-fraicheurs.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'USER']));
    $this->get(route('admin.point-fraicheurs.edit', $point))->assertForbidden();
    $this->post(route('admin.point-fraicheurs.store'), [])->assertForbidden();
    $this->put(route('admin.point-fraicheurs.update', $point), ['nom' => 'Forged'])->assertForbidden();
    $this->patch(route('admin.point-fraicheurs.toggle-status', $point))->assertForbidden();
    $this->delete(route('admin.point-fraicheurs.destroy', $point))->assertForbidden();
    $this->delete(route('admin.type-points.destroy', $point->typePoint))->assertForbidden();
    $this->assertDatabaseHas('point_fraicheurs', ['id' => $point->id, 'actif' => true]);
});

test('admin can manage cooling points and their publication without deleting used types', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    $quartier = Quartier::factory()->create();
    $this->post(route('admin.type-points.store'), ['nom' => 'Audit library', 'icone' => 'book'])
        ->assertRedirect();
    $type = TypePoint::where('nom', 'Audit library')->firstOrFail();
    $data = ['nom' => 'Community library', 'type_point_id' => $type->id, 'quartier_id' => $quartier->id,
        'adresse' => '12 Main Street', 'actif' => '1', 'accessible_pmr' => '1'];
    $this->post(route('admin.point-fraicheurs.store'), $data)->assertRedirect();
    $point = PointFraicheur::where('nom', 'Community library')->firstOrFail();
    $this->get(route('admin.point-fraicheurs.show', $point))->assertOk()->assertSee('Audit library');
    $this->get(route('admin.point-fraicheurs.edit', $point))->assertOk();
    $this->put(route('admin.point-fraicheurs.update', $point), array_replace($data, ['nom' => 'Updated library']))
        ->assertRedirect(route('admin.point-fraicheurs.show', $point));
    $this->patch(route('admin.point-fraicheurs.toggle-status', $point))->assertRedirect();
    expect($point->fresh()->actif)->toBeFalse();
    $this->get(route('cooling-points'))->assertOk()->assertDontSee('Updated library');
    $this->delete(route('admin.type-points.destroy', $type))->assertRedirect()->assertSessionHas('error');
    $this->assertDatabaseHas('type_points', ['id' => $type->id]);
    $this->delete(route('admin.point-fraicheurs.destroy', $point))->assertRedirect();
    $this->assertDatabaseMissing('point_fraicheurs', ['id' => $point->id]);
    $this->put(route('admin.type-points.update', $type), ['nom' => 'Renamed library', 'icone' => 'book'])->assertRedirect();
    $this->delete(route('admin.type-points.destroy', $type))->assertRedirect();
    $this->assertDatabaseMissing('type_points', ['id' => $type->id]);
});

test('cooling point validation rejects invalid parents and coordinates', function () {
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->post(route('admin.point-fraicheurs.store'), [
            'nom' => 'Invalid place', 'adresse' => '12 Main Street',
            'type_point_id' => 999999, 'quartier_id' => 999999, 'latitude' => 91, 'longitude' => 181,
        ])->assertSessionHasErrors(['type_point_id', 'quartier_id', 'latitude', 'longitude']);
    $this->assertDatabaseCount('point_fraicheurs', 0);
});

test('deleting a neighborhood used by a cooling point returns an error and preserves both records', function () {
    $point = PointFraicheur::factory()->create();
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->delete(route('admin.quartiers.destroy', $point->quartier))
        ->assertRedirect(route('admin.quartiers.index'))->assertSessionHas('error');
    $this->assertDatabaseHas('quartiers', ['id' => $point->quartier_id]);
    $this->assertDatabaseHas('point_fraicheurs', ['id' => $point->id]);
});
