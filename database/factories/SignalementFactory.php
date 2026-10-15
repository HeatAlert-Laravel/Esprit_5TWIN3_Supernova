<?php

namespace Database\Factories;

use App\Models\Coupure;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Signalement> */
class SignalementFactory extends Factory
{
    protected $model = Signalement::class;

    public function definition(): array
    {
        return [
            'coupure_id' => fake()->boolean(60) ? (Coupure::inRandomOrder()->value('id') ?? Coupure::factory()) : null,
            'user_id' => User::factory(),
            'adresse' => fake()->streetAddress(),
            'description' => 'Absence de courant dans le logement et les parties communes.',
            'statut' => fake()->randomElement(Signalement::STATUTS),
        ];
    }

    public function sansCoupure(): static
    {
        return $this->state(fn () => ['coupure_id' => null]);
    }
}
