<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_fraicheurs', function (Blueprint $table) {
            // Existing places remain unconfirmed until an administrator checks them.
            $table->boolean('eau_potable')->default(false);
            $table->boolean('ombre')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('point_fraicheurs', fn (Blueprint $table) => $table->dropColumn(['eau_potable', 'ombre']));
    }
};
