<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membre 3 - table ENFANT : un lieu frais réel.
     * Chaque point appartient à un TypePoint et à un Quartier (Membre 1).
     */
    public function up(): void
    {
        Schema::create('point_fraicheurs', function (Blueprint $table) {
            $table->id();

            // Clés étrangères.
            // restrictOnDelete : impossible de supprimer un type ou un quartier encore utilisé.
            $table->foreignId('type_point_id')->constrained('type_points')->restrictOnDelete();
            $table->foreignId('quartier_id')->constrained('quartiers')->restrictOnDelete();

            $table->string('nom', 150);
            $table->string('adresse');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('horaires', 100)->nullable(); // ex : "08:00 - 20:00"
            $table->boolean('accessible_pmr')->default(false); // accès mobilité réduite
            $table->boolean('actif')->default(true); // false = masqué côté résidents
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_fraicheurs');
    }
};
