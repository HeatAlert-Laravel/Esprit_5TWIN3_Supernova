<?php

namespace Database\Seeders;

use App\Models\Coupure;
use App\Models\Quartier;
use Illuminate\Database\Seeder;

class CoupureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Quartier::orderBy('id')->get() as $quartier) {
            Coupure::firstOrCreate(
                ['quartier_id' => $quartier->id, 'description' => 'Maintenance préventive du réseau électrique du quartier.'],
                ['type' => 'prévue', 'statut' => 'active', 'date_debut' => now()->addDay(), 'date_fin_estimee' => now()->addDay()->addHours(3)],
            );
            Coupure::firstOrCreate(
                ['quartier_id' => $quartier->id, 'description' => 'Incident sur un transformateur, intervention en cours.'],
                ['type' => 'en cours', 'statut' => 'active', 'date_debut' => now()->subHour(), 'date_fin_estimee' => null],
            );
        }
    }
}
