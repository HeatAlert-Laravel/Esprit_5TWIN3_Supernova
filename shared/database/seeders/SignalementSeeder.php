<?php

namespace Database\Seeders;

use App\Models\Coupure;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Seeder;

class SignalementSeeder extends Seeder
{
    public function run(): void
    {
        $coupure = Coupure::orderBy('id')->first();
        foreach (User::where('role', '!=', 'ADMIN')->orderBy('id')->limit(5)->get() as $user) {
            foreach ([null, $coupure?->id] as $coupureId) {
                Signalement::firstOrCreate(
                    ['user_id' => $user->id, 'coupure_id' => $coupureId, 'adresse' => '12 rue des Jardins', 'description' => 'Le logement et les parties communes sont sans électricité.'],
                    ['statut' => 'en attente'],
                );
            }
        }
    }
}
