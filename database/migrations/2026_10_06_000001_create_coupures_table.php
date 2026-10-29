<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quartier_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['prévue', 'en cours']);
            $table->enum('statut', ['active', 'résolue'])->default('active');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin_estimee')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['statut', 'quartier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupures');
    }
};
