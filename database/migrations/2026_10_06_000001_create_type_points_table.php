<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membre 3 - table PARENTE : catégories de lieux frais (parc, fontaine, salle climatisée...).
     * Relation : TypePoint 1 -> N PointFraicheur.
     */
    public function up(): void
    {
        Schema::create('type_points', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->unique(); // unique : pas deux types avec le même nom
            $table->string('icone', 50)->nullable(); // nom d'icône affiché dans les vues
            $table->text('description')->nullable();
            $table->timestamps(); // created_at + updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_points');
    }
};
