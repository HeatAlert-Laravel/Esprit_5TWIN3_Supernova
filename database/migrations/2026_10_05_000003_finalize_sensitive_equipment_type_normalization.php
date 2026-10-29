<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single source of truth: once every row is mapped to a TypeEquipement, the duplicated free-text
     * `type` and per-row `priority_level` are removed (TypeEquipement.name / risk_level replace them)
     * and the foreign key becomes mandatory. Aborts untouched if any row is still unmapped.
     */
    public function up(): void
    {
        $unmapped = DB::table('sensitive_equipments')->whereNull('type_equipement_id')->count();

        if ($unmapped > 0) {
            throw new RuntimeException("{$unmapped} sensitive_equipments row(s) have no type_equipement_id; aborting before dropping legacy columns.");
        }

        Schema::table('sensitive_equipments', function (Blueprint $table) {
            $table->dropColumn(['type', 'priority_level']);
        });

        Schema::table('sensitive_equipments', function (Blueprint $table) {
            $table->unsignedBigInteger('type_equipement_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sensitive_equipments', function (Blueprint $table) {
            $table->unsignedBigInteger('type_equipement_id')->nullable()->change();
            $table->string('type', 100)->default('household')->after('name');
            $table->string('priority_level', 10)->default('medium')->after('description');
        });

        // Re-derive the legacy values from the type (risk "critical" collapses back to the old "high").
        DB::table('sensitive_equipments')->update([
            'type' => DB::raw("case when type_equipement_id in (select id from type_equipements where name = 'Medical equipment') then 'medical' else 'household' end"),
            'priority_level' => DB::raw("(select case risk_level when 'critical' then 'high' else risk_level end from type_equipements where type_equipements.id = sensitive_equipments.type_equipement_id)"),
        ]);
    }
};
