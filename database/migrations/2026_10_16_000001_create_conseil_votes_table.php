<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conseil_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conseil_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('value');
            $table->timestamps();
            $table->unique(['user_id', 'conseil_id']);
            $table->index(['conseil_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conseil_votes');
    }
};
