<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use Illuminate\Database\Seeder;

class SensitiveEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Profile::all() as $profile) {
            if ($profile->sensitiveEquipments()->exists()) {
                continue;
            }

            SensitiveEquipment::factory()->for($profile)->count(2)->create();
        }
    }
}
