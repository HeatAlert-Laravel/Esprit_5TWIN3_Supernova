<?php

namespace Database\Seeders;

use App\Models\CategorieConseil;
use Illuminate\Database\Seeder;

class CategorieConseilSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['nom' => 'Everyday preparedness', 'icone' => 'lightbulb', 'description' => 'Small habits that make hot days easier to manage.'],
            ['nom' => 'Home & equipment', 'icone' => 'plug', 'description' => 'Prepare your living space and plan around power interruptions.'],
            ['nom' => 'Family & neighbors', 'icone' => 'users', 'description' => 'Make a shared plan and stay in touch with people around you.'],
            ['nom' => 'Out & about', 'icone' => 'sun', 'description' => 'Plan your route, find shade, and know where to take a break.'],
        ] as $category) {
            CategorieConseil::firstOrCreate(['nom' => $category['nom']], $category);
        }
    }
}
