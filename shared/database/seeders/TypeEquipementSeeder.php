<?php

namespace Database\Seeders;

use App\Models\TypeEquipement;
use Illuminate\Database\Seeder;

/** Fixed, realistic equipment types. Idempotent: safe to run repeatedly. */
class TypeEquipementSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Refrigerator', true, true, 'high'],
            ['Medical equipment', true, true, 'critical'],
            ['Aquarium', true, true, 'medium'],
            ['Freezer', true, true, 'high'],
            ['Fan', false, true, 'medium'],
            ['Air conditioner', false, true, 'medium'],
            ['Other', false, false, 'low'],
        ];

        foreach ($types as [$name, $heat, $outage, $risk]) {
            TypeEquipement::updateOrCreate(['name' => $name], [
                'sensitive_to_heat' => $heat,
                'sensitive_to_outage' => $outage,
                'risk_level' => $risk,
            ]);
        }
    }
}
