<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conseils', function (Blueprint $table) {
            $table->id();
            // A category cannot be deleted while advice still belongs to it.
            $table->foreignId('categorie_conseil_id')->constrained('categorie_conseils')->restrictOnDelete();
            $table->string('titre', 150);
            $table->string('resume', 300);
            $table->text('contenu');
            $table->enum('public_cible', ['everyone', 'seniors', 'parents', 'caregivers'])->default('everyone');
            $table->enum('situation', ['heatwave', 'outage', 'both'])->default('both');
            $table->boolean('actif')->default(false);
            $table->timestamps();
            $table->index(['actif', 'categorie_conseil_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conseils');
    }
};
