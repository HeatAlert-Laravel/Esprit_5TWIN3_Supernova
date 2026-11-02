<?php

namespace Database\Seeders;

use App\Models\PointFraicheur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Explicit local fixtures: real map pins with simulated facilities and schedules. */
class TunisCoolingPointDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Simulated cooling points are restricted to local/testing demonstration databases.');
        }

        $data = json_decode(file_get_contents(database_path('data/tunis-cooling-candidates.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            $this->call(TunisCoolingPointSeeder::class);

            foreach ($data['places'] as $place) {
                $real = PointFraicheur::where('nom', $place['nom'])->firstOrFail();
                PointFraicheur::firstOrCreate(['nom' => 'DEMO - '.$place['nom'].' (simulated amenities)'], [
                    'type_point_id' => $real->type_point_id,
                    'quartier_id' => $real->quartier_id,
                    'adresse' => 'DEMO ONLY - '.$place['adresse'],
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'horaires' => '24/7',
                    'actif' => true,
                    'eau_potable' => true,
                    'ombre' => true,
                    'accessible_pmr' => true,
                    'description' => 'CLASSROOM SIMULATION ONLY. The name and coordinates identify a real place; drinking water, shaded resting facilities, wheelchair access and 24/7 opening are fictional test assumptions. This is not a verified cooling point or a recommendation to visit. Source: '.$place['maps_url'],
                ]);
            }
        });

        $this->command?->warn('DEMO records added: all amenities and 24/7 schedules are SIMULATED. Use only on a private local demo.');
    }
}
