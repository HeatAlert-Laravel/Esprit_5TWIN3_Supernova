<?php

namespace Database\Seeders;

use App\Models\PointFraicheur;
use App\Models\Quartier;
use App\Models\TypePoint;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic cooling points in English, distributed across neighborhoods.
 */
class PointFraicheurSeeder extends Seeder
{
    public function run(): void
    {
        $quartiers = Quartier::orderBy('id')->get();

        if ($quartiers->isEmpty()) {
            return;
        }

        // [name, typeName, address, lat, lng, hours, prm, active, description]
        $points = [
            [
                'Belvédère Municipal Park',
                'Shaded park',
                '12 Forest Avenue',
                36.8189,
                10.1747,
                '07:00 - 20:00',
                true,
                true,
                'Large park with mature trees, shaded walkways, and free drinking water fountains.',
            ],
            [
                'Palm Shade Botanical Garden',
                'Shaded park',
                '45 Green Way',
                36.8065,
                10.1815,
                '08:00 - 19:30',
                false,
                true,
                'Historic shaded garden with cooling water basins and shaded benches.',
            ],
            [
                'Central Plaza Misting Station',
                'Drinking fountain & mister',
                'Republic Central Square',
                36.8008,
                10.1800,
                '24/7',
                true,
                true,
                'Automated misting jets and chilled drinking water station available at all hours.',
            ],
            [
                'Marketplace Water Fountain',
                'Drinking fountain & mister',
                '8 Market Street',
                36.7990,
                10.1700,
                '09:00 - 19:00',
                true,
                true,
                'Fresh potable water fountain located at the pedestrian entrance of the market.',
            ],
            [
                'Municipal Air-Conditioned Refuge',
                'Air-conditioned public hall',
                '3 Town Hall Square',
                36.8100,
                10.1650,
                '08:30 - 20:00',
                true,
                true,
                'Public cooling shelter kept at 23°C with seating and hydration supplies for vulnerable residents.',
            ],
            [
                'Neighborhood Community Center',
                'Air-conditioned public hall',
                '24 Culture Boulevard',
                36.8150,
                10.1900,
                '09:00 - 17:00',
                true,
                false, // Inactive demo point to test filter
                'Currently closed for scheduled electrical maintenance.',
            ],
            [
                'Bel-Air Olympic Pool',
                'Municipal swimming pool',
                '15 Sports Parkway',
                36.8200,
                10.1600,
                '08:00 - 20:00',
                true,
                true,
                'Indoor and outdoor pools with shaded terrace and lifeguard supervision.',
            ],
            [
                'Central Public Media Library',
                'Public library',
                '78 Liberty Street',
                36.8050,
                10.1850,
                '09:00 - 18:30',
                true,
                true,
                'Quiet, air-conditioned public reading spaces with free Wi-Fi and water fountains.',
            ],
        ];

        foreach ($points as $i => [$nom, $typeNom, $adresse, $lat, $lng, $horaires, $pmr, $actif, $desc]) {
            $type = TypePoint::where('nom', $typeNom)->first();

            if (! $type) {
                continue;
            }

            PointFraicheur::updateOrCreate(['nom' => $nom], [
                'type_point_id' => $type->id,
                'quartier_id' => $quartiers[$i % $quartiers->count()]->id,
                'adresse' => $adresse,
                'latitude' => $lat,
                'longitude' => $lng,
                'horaires' => $horaires,
                'accessible_pmr' => $pmr,
                'actif' => $actif,
                'description' => $desc,
            ]);
        }
    }
}
