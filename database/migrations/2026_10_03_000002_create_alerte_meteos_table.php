<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alerte_meteos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quartier_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->string('niveau', 10);
            $table->decimal('temperature_max', 5, 2);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('publiee')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerte_meteos');
    }
};