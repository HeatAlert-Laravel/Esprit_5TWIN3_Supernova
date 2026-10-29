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
            'type' => 'canicule',
            'niveau' => fake()->randomElement(['vert', 'jaune', 'orange', 'rouge']),
            'temperature_max' => fake()->randomFloat(2, 30, 44),
            'temperature_min' => fake()->randomFloat(2, 20, 29),
            'temperature_ressentie' => fake()->randomFloat(2, 31, 48),
            'humidite' => fake()->numberBetween(20, 70),
            'indice_uv' => fake()->numberBetween(3, 11),
            'vitesse_vent' => fake()->randomFloat(1, 5, 40),
            'risque_coupure' => fake()->boolean(30),
            'source' => 'Factory',
            'date_debut' => $start->format('Y-m-d'),
            'date_fin' => (clone $start)->modify('+2 days')->format('Y-m-d'),
            'publiee' => fake()->boolean(70),
        ];
    }
}