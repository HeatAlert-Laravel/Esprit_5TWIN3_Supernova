<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proves the additive migrations keep existing equipment: roll the normalization back to the old
 * schema, insert legacy rows (free-text type + priority_level), migrate again and check the mapping.
 */
beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    // Undo only the two normalization migrations (finalize, then add type_equipement_id).
    $this->artisan('migrate:rollback', ['--step' => 2, '--force' => true]);
});

function legacyEquipment(int $profileId, string $name, string $type, string $priority): int
{
    return DB::table('sensitive_equipments')->insertGetId([
        'profile_id' => $profileId, 'name' => $name, 'type' => $type, 'description' => null,
        'priority_level' => $priority, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('rolling back restores the legacy columns', function () {
    expect(Schema::hasColumns('sensitive_equipments', ['type', 'priority_level']))->toBeTrue()
        ->and(Schema::hasColumn('sensitive_equipments', 'type_equipement_id'))->toBeFalse();
});

test('existing equipment is mapped to canonical types and nothing is lost', function () {
    $profileId = App\Models\Profile::factory()->create()->id;
    $expected = [
        legacyEquipment($profileId, 'Medical respirator', 'medical', 'high') => 'Medical equipment',
        legacyEquipment($profileId, 'Refrigerator', 'household', 'low') => 'Refrigerator',
        legacyEquipment($profileId, 'Aquarium', 'household', 'low') => 'Aquarium',
        legacyEquipment($profileId, 'Air conditioner', 'household', 'high') => 'Air conditioner',
        legacyEquipment($profileId, 'Freezer', 'household', 'low') => 'Freezer',
        legacyEquipment($profileId, 'Bedroom fan', 'household', 'medium') => 'Fan',
        legacyEquipment($profileId, 'Insulin fridge', 'household', 'medium') => 'Medical equipment', // medical keyword wins
        legacyEquipment($profileId, 'Mystery box', 'medical', 'low') => 'Medical equipment', // legacy type fallback
        legacyEquipment($profileId, 'Mystery box', 'household', 'low') => 'Other',
    ];

    $this->artisan('migrate', ['--force' => true])->assertSuccessful();

    expect(DB::table('sensitive_equipments')->count())->toBe(count($expected));
    foreach ($expected as $id => $typeName) {
        $mapped = DB::table('sensitive_equipments')->join('type_equipements', 'type_equipements.id', '=', 'sensitive_equipments.type_equipement_id')
            ->where('sensitive_equipments.id', $id)->value('type_equipements.name');
        expect($mapped)->toBe($typeName);
    }
    expect(Schema::hasColumn('sensitive_equipments', 'type'))->toBeFalse()
        ->and(Schema::hasColumn('sensitive_equipments', 'priority_level'))->toBeFalse();
});

test('the finalize migration refuses to drop legacy columns while a row is unmapped', function () {
    $profileId = App\Models\Profile::factory()->create()->id;
    $this->artisan('migrate', ['--force' => true]);
    $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true]); // undo finalize only: legacy columns are back
    legacyEquipment($profileId, 'Unmapped', 'household', 'low'); // has no type_equipement_id

    expect(fn () => Artisan::call('migrate', ['--force' => true]))->toThrow(RuntimeException::class);
    expect(Schema::hasColumns('sensitive_equipments', ['type', 'priority_level']))->toBeTrue()
        ->and(DB::table('sensitive_equipments')->count())->toBe(1);
});
