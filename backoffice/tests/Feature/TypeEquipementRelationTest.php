<?php

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\User;
use Database\Seeders\ProfileSeeder;
use Database\Seeders\SensitiveEquipmentSeeder;
use Database\Seeders\TypeEquipementSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

// The official Module 5 pair: TypeEquipement 1 -> N SensitiveEquipment.
beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('TypeEquipement hasMany SensitiveEquipment', function () {
    $type = TypeEquipement::factory()->create();
    $items = SensitiveEquipment::factory()->count(3)->ofType($type)->create();

    expect($type->sensitiveEquipments())->toBeInstanceOf(HasMany::class)
        ->and($type->sensitiveEquipments)->toHaveCount(3)
        ->and($type->sensitiveEquipments->pluck('id')->sort()->values()->all())->toBe($items->pluck('id')->sort()->values()->all());
});

test('SensitiveEquipment belongsTo TypeEquipement and still belongsTo Profile', function () {
    $type = TypeEquipement::where('name', 'Aquarium')->firstOrFail();
    $profile = Profile::factory()->create();
    $item = SensitiveEquipment::factory()->for($profile)->ofType($type)->create();

    expect($item->typeEquipement())->toBeInstanceOf(BelongsTo::class)
        ->and($item->typeEquipement->is($type))->toBeTrue()
        ->and($item->profile->is($profile))->toBeTrue()
        ->and($profile->sensitiveEquipments->pluck('id')->all())->toContain($item->id);
});

test('the one source of truth: equipment no longer stores type or priority columns', function () {
    expect(Schema::hasColumn('sensitive_equipments', 'type'))->toBeFalse()
        ->and(Schema::hasColumn('sensitive_equipments', 'priority_level'))->toBeFalse()
        ->and(Schema::hasColumn('sensitive_equipments', 'type_equipement_id'))->toBeTrue()
        ->and(Schema::hasColumns('type_equipements', ['name', 'sensitive_to_heat', 'sensitive_to_outage', 'risk_level']))->toBeTrue();
});

test('changing a type changes the risk and sensitivity of every equipment of that type', function () {
    $type = TypeEquipement::factory()->create(['risk_level' => 'low', 'sensitive_to_heat' => false, 'sensitive_to_outage' => false]);
    $items = SensitiveEquipment::factory()->count(2)->ofType($type)->create();

    $type->update(['risk_level' => 'critical', 'sensitive_to_heat' => true]);

    foreach ($items as $item) {
        $fresh = $item->fresh()->load('typeEquipement');
        expect($fresh->typeEquipement->risk_level)->toBe('critical')->and($fresh->typeEquipement->sensitive_to_heat)->toBeTrue();
    }
});

test('an unknown type_equipement_id is rejected by validation and by the database', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $profile = Profile::factory()->create();

    $this->actingAs($admin)->post(route('admin.equipment.store'), ['profile_id' => $profile->id, 'name' => 'Ghost', 'type_equipement_id' => 987654])
        ->assertSessionHasErrors('type_equipement_id');
    $this->post(route('admin.equipment.store'), ['profile_id' => $profile->id, 'name' => 'Ghost'])->assertSessionHasErrors('type_equipement_id');
    $this->assertDatabaseCount('sensitive_equipments', 0);

    // Even bypassing validation, the foreign key stops an orphan row.
    Schema::enableForeignKeyConstraints();
    expect(fn () => SensitiveEquipment::create(['profile_id' => $profile->id, 'name' => 'Ghost', 'type_equipement_id' => 987654]))
        ->toThrow(Illuminate\Database\QueryException::class);
});

test('factories never create orphan foreign keys', function () {
    $items = SensitiveEquipment::factory()->count(5)->create();

    foreach ($items as $item) {
        expect($item->typeEquipement)->toBeInstanceOf(TypeEquipement::class)->and($item->profile)->toBeInstanceOf(Profile::class);
    }

    SensitiveEquipment::query()->delete(); // equipment first: a used type cannot be deleted
    TypeEquipement::query()->delete();
    $created = SensitiveEquipment::factory()->create(); // no type exists: the factory creates one
    expect($created->typeEquipement)->toBeInstanceOf(TypeEquipement::class);
});

test('type seeder creates the fixed realistic types idempotently', function () {
    TypeEquipement::where('name', 'Fan')->update(['risk_level' => 'low']); // drifted value is restored

    $this->seed(TypeEquipementSeeder::class);
    $this->seed(TypeEquipementSeeder::class);

    expect(TypeEquipement::count())->toBe(7);
    $expected = [
        'Refrigerator' => [true, true, 'high'], 'Medical equipment' => [true, true, 'critical'], 'Aquarium' => [true, true, 'medium'],
        'Freezer' => [true, true, 'high'], 'Fan' => [false, true, 'medium'], 'Air conditioner' => [false, true, 'medium'], 'Other' => [false, false, 'low'],
    ];
    foreach ($expected as $name => [$heat, $outage, $risk]) {
        $type = TypeEquipement::where('name', $name)->firstOrFail();
        expect([$type->sensitive_to_heat, $type->sensitive_to_outage, $type->risk_level])->toBe([$heat, $outage, $risk]);
    }
    expect(TypeEquipement::pluck('risk_level')->unique()->diff(TypeEquipement::RISK_LEVELS))->toBeEmpty();
});

test('demo equipment seeder links every item to a real type', function () {
    $this->seed([ProfileSeeder::class, SensitiveEquipmentSeeder::class]);

    expect(SensitiveEquipment::count())->toBe(6)->and(SensitiveEquipment::whereNull('type_equipement_id')->count())->toBe(0);
    expect(SensitiveEquipment::where('name', 'Oxygen concentrator')->first()->typeEquipement->name)->toBe('Medical equipment');
});

test('highRisk scope returns only high and critical equipment', function () {
    $types = fn (string $n) => TypeEquipement::where('name', $n)->firstOrFail();
    $critical = SensitiveEquipment::factory()->ofType($types('Medical equipment'))->create();
    $high = SensitiveEquipment::factory()->ofType($types('Freezer'))->create();
    SensitiveEquipment::factory()->ofType($types('Fan'))->create();
    SensitiveEquipment::factory()->ofType($types('Other'))->create();

    expect(SensitiveEquipment::highRisk()->pluck('id')->sort()->values()->all())->toBe(collect([$critical->id, $high->id])->sort()->values()->all());
});
