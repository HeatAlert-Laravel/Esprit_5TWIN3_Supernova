<?php

namespace Database\Factories;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AlerteMeteo> */
class AlerteMeteoFactory extends Factory
{
    protected $model = AlerteMeteo::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+14 days');

        return [
            'quartier_id' => Quartier::factory(),
            'titre' => 'Vigilance chaleur - '.fake()->city(),
            'niveau' => fake()->randomElement(['vert', 'jaune', 'orange', 'rouge']),
            'temperature_max' => fake()->randomFloat(2, 30, 44),
            'date_debut' => $start->format('Y-m-d'),
            'date_fin' => (clone $start)->modify('+2 days')->format('Y-m-d'),
            'publiee' => fake()->boolean(70),
        ];
    }
}