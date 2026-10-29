<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conseils', function (Blueprint $table) {
            $table->json('contenu_formate')->nullable();
        });

        Schema::create('conseil_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conseil_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'conseil_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conseil_user');
        Schema::table('conseils', fn (Blueprint $table) => $table->dropColumn('contenu_formate'));
    }
};
