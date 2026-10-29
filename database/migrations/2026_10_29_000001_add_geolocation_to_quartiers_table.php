<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quartiers', function (Blueprint $table) {
            $table->string('gouvernorat')->nullable()->after('ville');
            $table->decimal('latitude', 10, 7)->nullable()->after('code_postal');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('quartiers', function (Blueprint $table) {
            $table->dropColumn(['gouvernorat', 'latitude', 'longitude']);
        });
    }
};
