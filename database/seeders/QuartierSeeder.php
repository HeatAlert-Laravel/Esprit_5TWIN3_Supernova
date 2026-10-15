<?php

namespace Database\Seeders;

use App\Models\Quartier;
use Illuminate\Database\Seeder;

class QuartierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nom' => 'La Marsa', 'ville' => 'Tunis', 'code_postal' => '1053'],
            ['nom' => 'Le Bardo', 'ville' => 'Tunis', 'code_postal' => '2000'],
            ['nom' => 'Carthage', 'ville' => 'Tunis', 'code_postal' => '2016'],
            ['nom' => 'Sidi Bou Said', 'ville' => 'Tunis', 'code_postal' => '2026'],
            ['nom' => 'El Menzah', 'ville' => 'Tunis', 'code_postal' => '2092'],
            ['nom' => 'Ben Arous', 'ville' => 'Ben Arous', 'code_postal' => '2013'],
            ['nom' => 'Mghira', 'ville' => 'Ben Arous', 'code_postal' => '2082'],
            ['nom' => 'Ariana', 'ville' => 'Ariana', 'code_postal' => '2080'],
        ] as $quartier) {
            Quartier::firstOrCreate(
                ['nom' => $quartier['nom'], 'ville' => $quartier['ville']],
                ['code_postal' => $quartier['code_postal']],
            );
        }
    }
}