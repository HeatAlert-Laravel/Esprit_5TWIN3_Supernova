<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\TypeEquipement;
use Illuminate\Database\Seeder;

class SensitiveEquipmentSeeder extends Seeder
{
    /** Demo household => [type name, equipment name, notes]. */
    private const DEMO_EQUIPMENT = [
        'amina@example.test' => [
            ['Refrigerator', 'Kitchen refrigerator', 'Stores the family food.'],
            ['Fan', 'Bedroom fan', 'Main cooling during hot nights.'],
        ],
        'youssef@example.test' => [
            ['Medical equipment', 'Oxygen concentrator', 'Used every night.'],
            ['Freezer', 'Garage freezer', null],
        ],
        'leila@example.test' => [
            ['Aquarium', 'Living room aquarium', 'Tropical fish, needs a stable temperature.'],
            ['Air conditioner', 'Living room air conditioner', null],
        ],
    ];

    public function run(): void
    {
        $this->call(TypeEquipementSeeder::class);

        $typeIds = TypeEquipement::pluck('id', 'name');

        foreach (self::DEMO_EQUIPMENT as $email => $items) {
            $profile = Profile::whereHas('user', fn ($query) => $query->where('email', $email))->first();

            if (! $profile || $profile->sensitiveEquipments()->exists()) {
                continue;
            }

            foreach ($items as [$typeName, $name, $notes]) {
                $profile->sensitiveEquipments()->create([
                    'type_equipement_id' => $typeIds[$typeName],
                    'name' => $name,
                    'description' => $notes,
                ]);
            }
        }
    }
}
