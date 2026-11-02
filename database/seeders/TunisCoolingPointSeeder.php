<?php

namespace Database\Seeders;

use App\Models\PointFraicheur;
use App\Models\Quartier;
use App\Models\TypePoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Real place identities and map pins; eligibility requires an administrator's checks. */
class TunisCoolingPointSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/tunis-cooling-candidates.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            foreach ($data['places'] as $place) {
                $quartier = Quartier::firstOrCreate(
                    ['nom' => $place['quartier'], 'ville' => 'Tunis'],
                    ['code_postal' => $place['code_postal'], 'gouvernorat' => 'Tunis'],
                );
                $type = TypePoint::firstOrCreate(
                    ['nom' => $place['type']],
                    ['icone' => $place['icon'], 'description' => 'Place category; amenities and public access require individual confirmation.'],
                );

                // Preserve existing records, including an administrator's later verification.
                PointFraicheur::firstOrCreate(['nom' => $place['nom']], [
                    'type_point_id' => $type->id,
                    'quartier_id' => $quartier->id,
                    'adresse' => $place['adresse'],
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'horaires' => $place['horaires'],
                    'actif' => false,
                    'eau_potable' => false,
                    'ombre' => false,
                    'accessible_pmr' => false,
                    'description' => 'REAL PLACE - awaiting cooling-point verification. '.$place['notes']
                        .' Map pin checked '.$data['checked_on'].'. '.$place['maps_url']
                        .' Sources: '.implode(' ', $place['sources']),
                ]);
            }
        });

        $this->command?->info('Real Tunis places added for admin review. Existing records preserved; no amenities certified.');
    }
}
