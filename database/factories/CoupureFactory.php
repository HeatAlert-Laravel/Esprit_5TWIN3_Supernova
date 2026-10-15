<?php

namespace Database\Factories;

use App\Models\Coupure;
use App\Models\Quartier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupure> */
class CoupureFactory extends Factory
{
    protected $model = Coupure::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 days', '+7 days');

        return [
            'quartier_id' => Quartier::factory(),
            'type' => $start > now() ? 'prévue' : 'en cours',
            'statut' => fake()->randomElement(Coupure::STATUTS),
            'date_debut' => $start,
            'date_fin_estimee' => fake()->boolean(75) ? (clone $start)->modify('+3 hours') : null,
            'description' => fake()->randomElement(['Maintenance du réseau électrique.', 'Incident sur un transformateur du quartier.', 'Interruption liée à une surcharge du réseau.']),
        ];
    }
}
