<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alerte_meteos', function (Blueprint $table) {
            $table->string('type', 30)->default('canicule')->after('titre');
            $table->decimal('temperature_min', 5, 2)->nullable()->after('temperature_max');
            $table->decimal('temperature_ressentie', 5, 2)->nullable()->after('temperature_min');
            $table->unsignedTinyInteger('humidite')->nullable()->after('temperature_ressentie');
            $table->unsignedTinyInteger('indice_uv')->nullable()->after('humidite');
            $table->decimal('vitesse_vent', 5, 1)->nullable()->after('indice_uv');
            $table->boolean('risque_coupure')->default(false)->after('vitesse_vent');
            $table->string('source', 100)->nullable()->after('risque_coupure');
        });
    }

    public function down(): void
    {
        Schema::table('alerte_meteos', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'temperature_min', 'temperature_ressentie', 'humidite',
                'indice_uv', 'vitesse_vent', 'risque_coupure', 'source',
            ]);
        });
    }
};
