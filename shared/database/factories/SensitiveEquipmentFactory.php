<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SensitiveEquipment> */
class SensitiveEquipmentFactory extends Factory
{
    protected $model = SensitiveEquipment::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Refrigerator', 'Freezer', 'Medical respirator', 'Aquarium', 'Air conditioner']);

        return [
            'profile_id' => Profile::factory(),
            'name' => $name,
            'type' => $name === 'Medical respirator' ? 'medical' : 'household',
            'description' => 'Needs power or cooling during periods of extreme heat.',
            'priority_level' => $name === 'Medical respirator' ? 'high' : fake()->randomElement(['low', 'medium', 'high']),
        ];
    }
}
