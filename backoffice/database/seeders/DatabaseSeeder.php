<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (env('DEMO_ADMIN_EMAIL') && env('DEMO_ADMIN_PASSWORD')) {
            User::firstOrCreate(['email' => env('DEMO_ADMIN_EMAIL')], [
                'name' => 'HeatAlert Admin',
                'password' => env('DEMO_ADMIN_PASSWORD'),
                'role' => 'ADMIN',
            ]);
        }

        $this->call([ProfileSeeder::class, SensitiveEquipmentSeeder::class]);
    }
}
