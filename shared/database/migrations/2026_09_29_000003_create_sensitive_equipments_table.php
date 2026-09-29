<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sensitive_equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 100);
            $table->text('description')->nullable();
            $table->string('priority_level', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_equipments');
    }
};
