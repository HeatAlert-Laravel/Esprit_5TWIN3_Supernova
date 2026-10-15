<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['amina@example.test' => 'Amina Ben Salah', 'youssef@example.test' => 'Youssef Trabelsi', 'leila@example.test' => 'Leila Mansour'] as $email => $name) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => str()->random(40),
                'role' => 'USER',
            ]);

            if (! $user->profile) {
                Profile::factory()->for($user)->create();
            }
        }
    }
}
