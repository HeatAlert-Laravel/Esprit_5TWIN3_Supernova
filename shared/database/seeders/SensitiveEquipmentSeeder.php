<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use Illuminate\Database\Seeder;

class SensitiveEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $demoEmails = ['amina@example.test', 'youssef@example.test', 'leila@example.test'];

        foreach (Profile::whereHas('user', fn ($query) => $query->whereIn('email', $demoEmails))->get() as $profile) {
            if ($profile->sensitiveEquipments()->exists()) {
                continue;
            }

            SensitiveEquipment::factory()->for($profile)->count(2)->create();
        }
    }
}
