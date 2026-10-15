<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local') && env('DEMO_ADMIN_EMAIL') && env('DEMO_ADMIN_PASSWORD')) {
            $admin = User::firstOrNew(['email' => env('DEMO_ADMIN_EMAIL')]);
            $admin->name = 'HeatAlert Admin';
            $admin->role = 'ADMIN';

            if (! $admin->exists || ! Hash::check(env('DEMO_ADMIN_PASSWORD'), $admin->password)) {
                $admin->password = Hash::make(env('DEMO_ADMIN_PASSWORD'));
            }

            $admin->save();
        }

        // Shared entry point: parents are always seeded before their children.
        $this->call([
            QuartierSeeder::class,
            AlerteMeteoSeeder::class,
            ProfileSeeder::class,
            TypeEquipementSeeder::class,
            SensitiveEquipmentSeeder::class,
            CoupureSeeder::class,
            SignalementSeeder::class,
            TypePointSeeder::class,
            PointFraicheurSeeder::class,
            CategorieConseilSeeder::class,
            ConseilSeeder::class,
        ]);
    }
}
