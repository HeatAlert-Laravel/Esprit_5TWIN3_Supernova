<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alerte_meteos', function (Blueprint $table) {
            // True for alerts created by `alerts:generate`; an administrator editing one makes it manual again.
            $table->boolean('generee_auto')->default(false)->after('source');
            $table->index(['quartier_id', 'generee_auto', 'date_debut']);
        });
    }

    public function down(): void
    {
        Schema::table('alerte_meteos', function (Blueprint $table) {
            $table->dropIndex(['quartier_id', 'generee_auto', 'date_debut']);
            $table->dropColumn('generee_auto');
        });
    }
};
