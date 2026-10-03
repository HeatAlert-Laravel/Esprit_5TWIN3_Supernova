<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module 5 parent entity. Column names are English, like the rest of the existing schema
     * (the class/table name TypeEquipement / type_equipements follows the official module guide).
     */
    public function up(): void
    {
        Schema::create('type_equipements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('sensitive_to_heat')->default(false);
            $table->boolean('sensitive_to_outage')->default(false);
            $table->string('risk_level', 10); // low | medium | high | critical
            $table->timestamps();
        });

        // Canonical types are part of the schema baseline so a plain `php artisan migrate` is enough
        // for existing equipment to be mapped. (Frozen snapshot: TypeEquipementSeeder keeps them in sync.)
        $now = now();
        $types = [
            ['Refrigerator', true, true, 'high'],
            ['Medical equipment', true, true, 'critical'],
            ['Aquarium', true, true, 'medium'],
            ['Freezer', true, true, 'high'],
            ['Fan', false, true, 'medium'],
            ['Air conditioner', false, true, 'medium'],
            ['Other', false, false, 'low'], // fallback for equipment that cannot be classified
        ];

        foreach ($types as [$name, $heat, $outage, $risk]) {
            DB::table('type_equipements')->insert([
                'name' => $name, 'sensitive_to_heat' => $heat, 'sensitive_to_outage' => $outage,
                'risk_level' => $risk, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('type_equipements');
    }
};
