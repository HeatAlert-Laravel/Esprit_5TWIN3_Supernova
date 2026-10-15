<?php

namespace Database\Seeders;

use App\Models\TypePoint;
use Illuminate\Database\Seeder;

/**
 * Seeds the 5 canonical cooling place types in English.
 */
class TypePointSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Shaded park', 'icone' => 'tree', 'description' => 'Outdoor public green space with dense tree canopy and shaded benches.'],
            ['nom' => 'Drinking fountain & mister', 'icone' => 'droplet', 'description' => 'Potable water tap, refresh fountain, or outdoor misting station.'],
            ['nom' => 'Air-conditioned public hall', 'icone' => 'snowflake', 'description' => 'Public municipal facility equipped with active cooling for heat emergencies.'],
            ['nom' => 'Municipal swimming pool', 'icone' => 'waves', 'description' => 'Supervised public aquatic pool with shaded resting zones.'],
            ['nom' => 'Public library', 'icone' => 'book', 'description' => 'Quiet, climate-controlled municipal space open to residents during hot hours.'],
        ];

        foreach ($types as $type) {
            TypePoint::updateOrCreate(['nom' => $type['nom']], $type);
        }
    }
}
