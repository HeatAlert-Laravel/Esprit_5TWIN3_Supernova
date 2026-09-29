<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Profile> */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => fake()->numerify('+216 ## ### ###'),
            'address' => fake()->streetAddress(),
            'neighborhood' => fake()->randomElement(['La Marsa', 'Le Bardo', 'Carthage', 'Sidi Bou Said']),
            'has_fragile_person' => fake()->boolean(30),
        ];
    }
}
