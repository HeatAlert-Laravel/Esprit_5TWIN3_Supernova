<?php

namespace Database\Factories;

use App\Models\Quartier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quartier> */
class QuartierFactory extends Factory
{
    protected $model = Quartier::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->randomElement(['La Marsa', 'Le Bardo', 'Carthage', 'Sidi Bou Said', 'El Menzah']),
            'ville' => 'Tunis',
            'code_postal' => fake()->randomElement(['1053', '2000', '2016', '2026', '2092']),
        ];
    }
}