<?php

namespace Database\Seeders;

use App\Models\Quartier;
use Illuminate\Database\Seeder;

class QuartierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nom' => 'La Marsa', 'ville' => 'Tunis', 'code_postal' => '1053', 'latitude' => 36.8782, 'longitude' => 10.3247],
            ['nom' => 'Le Bardo', 'ville' => 'Tunis', 'code_postal' => '2000', 'latitude' => 36.8092, 'longitude' => 10.1346],
            ['nom' => 'Carthage', 'ville' => 'Tunis', 'code_postal' => '2016', 'latitude' => 36.8528, 'longitude' => 10.3233],
            ['nom' => 'Sidi Bou Said', 'ville' => 'Tunis', 'code_postal' => '2026', 'latitude' => 36.8702, 'longitude' => 10.3412],
            ['nom' => 'El Menzah', 'ville' => 'Tunis', 'code_postal' => '2092', 'latitude' => 36.8419, 'longitude' => 10.1825],
            ['nom' => 'Ben Arous', 'ville' => 'Ben Arous', 'code_postal' => '2013', 'latitude' => 36.7531, 'longitude' => 10.2189],
            ['nom' => 'Mghira', 'ville' => 'Ben Arous', 'code_postal' => '2082', 'latitude' => 36.7292, 'longitude' => 10.2158],
            ['nom' => 'Ariana', 'ville' => 'Ariana', 'code_postal' => '2080', 'latitude' => 36.8665, 'longitude' => 10.1647],
        ] as $data) {
            $quartier = Quartier::firstOrCreate(
                ['nom' => $data['nom'], 'ville' => $data['ville']],
                ['code_postal' => $data['code_postal']],
            );

            // Fill the new details on existing rows without overwriting an administrator's edits.
            $quartier->gouvernorat ??= $data['ville'];
            $quartier->latitude ??= $data['latitude'];
            $quartier->longitude ??= $data['longitude'];
            $quartier->save();
        }
    }
}