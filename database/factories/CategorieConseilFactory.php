<?php

namespace Database\Factories;

use App\Models\CategorieConseil;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CategorieConseil> */
class CategorieConseilFactory extends Factory
{
    protected $model = CategorieConseil::class;

    public function definition(): array
    {
        return [
            'nom' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'icone' => fake()->randomElement(array_keys(CategorieConseil::ICONS)),
        ];
    }
}
