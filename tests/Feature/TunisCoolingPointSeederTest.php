<?php

use App\Models\PointFraicheur;
use App\Services\CoolingPointFinder;
use Database\Seeders\TunisCoolingPointDemoSeeder;
use Database\Seeders\TunisCoolingPointSeeder;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
});

test('real places stay out of public recommendations until checked and reseeding preserves administrator edits', function () {
    $this->seed(TunisCoolingPointSeeder::class);
    expect(PointFraicheur::count())->toBe(4)
        ->and(PointFraicheur::actifs()->count())->toBe(0)
        ->and(app(CoolingPointFinder::class)->nearest(36.805, 10.178, false))->toBeNull();

    $garden = PointFraicheur::where('nom', 'Jardin Habib Thameur')->firstOrFail();
    expect($garden->quartier->nom)->toBe('Tunis Centre')
        ->and($garden->eau_potable)->toBeFalse()->and($garden->ombre)->toBeFalse();
    $garden->update(['adresse' => 'Administrator checked entrance', 'eau_potable' => true, 'ombre' => true, 'actif' => true]);

    $this->seed(TunisCoolingPointSeeder::class);
    expect(PointFraicheur::count())->toBe(4)
        ->and($garden->fresh()->adresse)->toBe('Administrator checked entrance')
        ->and($garden->fresh()->eau_potable)->toBeTrue()
        ->and(app(CoolingPointFinder::class)->nearest(36.805, 10.178, false)['id'])->toBe($garden->id);
});

test('local demo records are labelled and eligible without certifying the real places', function () {
    $this->seed(TunisCoolingPointDemoSeeder::class);
    $this->seed(TunisCoolingPointDemoSeeder::class);

    expect(PointFraicheur::count())->toBe(8)->and(PointFraicheur::actifs()->count())->toBe(4);
    foreach ([[36.805, 10.178, 'Jardin Habib Thameur'], [36.848, 10.279, 'Tunisia Mall']] as [$lat, $lng, $name]) {
        $nearest = app(CoolingPointFinder::class)->nearest($lat, $lng, true);
        expect($nearest)->not->toBeNull()
            ->and($nearest['name'])->toBe('DEMO - '.$name.' (simulated amenities)')
            ->and($nearest['address'])->toStartWith('DEMO ONLY - ')
            ->and($nearest['distance_km'])->toBeLessThan(0.1);
        $real = PointFraicheur::where('nom', $name)->firstOrFail();
        expect($real->actif)->toBeFalse()->and($real->eau_potable)->toBeFalse()->and($real->ombre)->toBeFalse();
    }
    expect(app(CoolingPointFinder::class)->nearest(35.0, 9.0, false))->toBeNull();
});

test('simulated data is refused outside local and testing before any records are written', function () {
    $previousEnvironment = app()['env'];
    app()['env'] = 'production';
    try {
        expect(fn () => app(TunisCoolingPointDemoSeeder::class)->run())->toThrow(RuntimeException::class);
        expect(PointFraicheur::count())->toBe(0);
    } finally {
        app()['env'] = $previousEnvironment;
    }
});
