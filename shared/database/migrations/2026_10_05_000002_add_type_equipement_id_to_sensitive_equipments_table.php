<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapping rules for existing rows (first match wins, on the lower-cased equipment name):
     *   medical / respirator / oxygen / cpap / insulin / dialysis / nebuli*  -> Medical equipment
     *   freezer / congelateur                                               -> Freezer
     *   refrigerator / fridge / frigo / refrigerateur                       -> Refrigerator
     *   aquarium                                                            -> Aquarium
     *   air conditioner / air-conditioning / aircon / a/c / climatis*       -> Air conditioner
     *   fan / ventilat*                                                     -> Fan
     *   legacy `type` column equal to "medical"                             -> Medical equipment
     *   anything else                                                       -> Other
     */
    private const RULES = [
        '/medical|respirator|oxygen|cpap|insulin|dialysis|nebuli/u' => 'Medical equipment',
        '/freezer|cong[eé]lateur/u' => 'Freezer',
        '/refrigerator|fridge|frigo|r[eé]frig[eé]rateur/u' => 'Refrigerator',
        '/aquarium/u' => 'Aquarium',
        '/air[ -]?condition|aircon|\ba\/c\b|climatis/u' => 'Air conditioner',
        '/\bfan\b|ventilat/u' => 'Fan',
    ];

    public function up(): void
    {
        Schema::table('sensitive_equipments', function (Blueprint $table) {
            // Nullable first so existing rows stay valid; restrictOnDelete means a used type can never be deleted.
            $table->foreignId('type_equipement_id')->nullable()->after('profile_id')
                ->constrained('type_equipements')->restrictOnDelete();
        });

        $typeIds = DB::table('type_equipements')->pluck('id', 'name');

        foreach (DB::table('sensitive_equipments')->get(['id', 'name', 'type']) as $row) {
            DB::table('sensitive_equipments')->where('id', $row->id)->update([
                'type_equipement_id' => $typeIds[$this->typeNameFor((string) $row->name, (string) $row->type)],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sensitive_equipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('type_equipement_id');
        });
    }

    private function typeNameFor(string $name, string $legacyType): string
    {
        $haystack = mb_strtolower($name);

        foreach (self::RULES as $pattern => $typeName) {
            if (preg_match($pattern, $haystack)) {
                return $typeName;
            }
        }

        return mb_strtolower($legacyType) === 'medical' ? 'Medical equipment' : 'Other';
    }
};
