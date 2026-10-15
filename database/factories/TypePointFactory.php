<?php

namespace Database\Factories;

use App\Models\TypePoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Génère des types de points fictifs (utile pour les tests).
 *
 * @extends Factory<TypePoint>
 */
class TypePointFactory extends Factory
{
    protected $model = TypePoint::class;

    public function definition(): array
    {
        return [
            // unique() : évite de violer la contrainte UNIQUE sur la colonne nom
            'nom' => ucfirst(fake()->unique()->words(2, true)),
            'icone' => fake()->randomElement(TypePoint::ICONS),
            'description' => fake()->sentence(),
        ];
    }
}
