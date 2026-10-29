<?php

namespace Database\Factories;

use App\Models\PointFraicheur;
use App\Models\Quartier;
use App\Models\TypePoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Génère des points de fraîcheur fictifs.
 *
 * @extends Factory<PointFraicheur>
 */
class PointFraicheurFactory extends Factory
{
    protected $model = PointFraicheur::class;

    public function definition(): array
    {
        return [
            // Une factory pour la clé étrangère : crée le parent automatiquement si on n'en fournit pas.
            'type_point_id' => TypePoint::factory(),
            'quartier_id' => Quartier::factory(),
            'nom' => 'Point frais '.fake()->unique()->numberBetween(1, 9999),
            'adresse' => fake()->streetAddress(),
            'latitude' => fake()->latitude(36.75, 36.85),
            'longitude' => fake()->longitude(10.10, 10.25),
            'horaires' => fake()->randomElement(['08:00 - 20:00', '09:00 - 18:00', '24h/24']),
            'accessible_pmr' => fake()->boolean(70),
            'actif' => true,
            'description' => fake()->sentence(12),
        ];
    }

    /** État : $factory->inactif() crée un point masqué. */
    public function inactif(): static
    {
        return $this->state(['actif' => false]);
    }
}
