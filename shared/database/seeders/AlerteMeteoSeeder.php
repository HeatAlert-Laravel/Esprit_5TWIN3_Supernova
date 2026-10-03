<?php

namespace Database\Seeders;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Database\Seeder;

class AlerteMeteoSeeder extends Seeder
{
    public function run(): void
    {
        $alerts = [
            ['quartier' => 'La Marsa', 'titre' => 'Forte chaleur attendue', 'niveau' => 'orange', 'temperature_max' => 39.5, 'publiee' => true],
            ['quartier' => 'Le Bardo', 'titre' => 'Vigilance chaleur', 'niveau' => 'jaune', 'temperature_max' => 34.0, 'publiee' => true],
        ];

        foreach ($alerts as $alert) {
            $quartier = Quartier::where('nom', $alert['quartier'])->first();
            $dates = ['date_debut' => now()->toDateString(), 'date_fin' => now()->addDays(2)->toDateString()];

            if ($quartier) {
                AlerteMeteo::firstOrCreate(
                    ['quartier_id' => $quartier->id, 'titre' => $alert['titre']],
                    [
                        'niveau' => $alert['niveau'],
                        'temperature_max' => $alert['temperature_max'],
                        'publiee' => $alert['publiee'],
                        ...$dates,
                    ],
                );
            }
        }
    }
}