<?php

namespace Database\Seeders;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Database\Seeder;

/**
 * Local demo heat alerts covering every state the screens display: all four levels
 * (vert, jaune, orange, rouge), published and draft, active, upcoming and expired.
 * Idempotent: an alert is identified by its neighborhood and title.
 * Day offsets are relative to the day the seeder first creates the alert.
 */
class AlerteMeteoSeeder extends Seeder
{
    public function run(): void
    {
        // [neighborhood, title, level, max temperature, start offset, end offset, published]
        $alerts = [
            ['La Marsa', 'Forte chaleur attendue', 'orange', 39.5, 0, 2, true],
            ['Le Bardo', 'Vigilance chaleur', 'jaune', 34.0, 0, 2, true],
            ['Carthage', 'Chaleur extrême', 'rouge', 43.0, -1, 3, true],
            ['Ariana', 'Canicule persistante', 'rouge', 41.5, 0, 4, true],
            ['Ben Arous', 'Vigilance chaleur à venir', 'jaune', 35.0, 2, 4, true],
            ['Mghira', 'Chaleur zone industrielle', 'orange', 38.0, 1, 3, true],
            ['Sidi Bou Said', 'Temps chaud sans danger', 'vert', 32.0, 0, 1, true],
            ['El Menzah', 'Chaleur modérée (brouillon)', 'jaune', 35.5, 0, 2, false],
            ['Carthage', 'Pic de chaleur terminé', 'orange', 40.0, -10, -8, true],
            ['La Marsa', 'Vague de chaleur terminée', 'rouge', 42.0, -7, -4, true],
            ['Le Bardo', 'Canicule annoncée (brouillon)', 'rouge', 43.5, 5, 7, false],
            ['Ariana', 'Vigilance fin de semaine', 'jaune', 33.5, 6, 8, true],
            ['Ben Arous', 'Chaleur extrême (brouillon)', 'rouge', 41.0, 3, 5, false],
            ['Le Bardo', 'Alerte levée', 'vert', 31.0, -15, -12, true],
            ['Mghira', 'Vigilance annulée (brouillon)', 'jaune', 34.5, -5, -3, false],
            ['Carthage', 'Forte chaleur en soirée', 'orange', 38.5, 1, 2, true],
        ];

        foreach ($alerts as [$name, $title, $level, $temperature, $start, $end, $published]) {
            $quartier = Quartier::where('nom', $name)->first();

            if (! $quartier) {
                continue;
            }

            AlerteMeteo::firstOrCreate(
                ['quartier_id' => $quartier->id, 'titre' => $title],
                [
                    'niveau' => $level,
                    'temperature_max' => $temperature,
                    'date_debut' => now()->addDays($start)->toDateString(),
                    'date_fin' => now()->addDays($end)->toDateString(),
                    'publiee' => $published,
                ],
            );
        }
    }
}
