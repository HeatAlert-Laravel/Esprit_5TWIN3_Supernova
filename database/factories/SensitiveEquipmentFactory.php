<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SensitiveEquipment> */
class SensitiveEquipmentFactory extends Factory
{
    protected $model = SensitiveEquipment::class;

    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            // Always a real TypeEquipement (never an orphan FK): reuse an existing one, else create one.
            'type_equipement_id' => fn () => TypeEquipement::query()->inRandomOrder()->value('id')
                ?? TypeEquipement::factory()->create()->id,
            // Named after its type unless a test overrides it.
            'name' => fn (array $attributes) => TypeEquipement::findOrFail($attributes['type_equipement_id'])->name,
            'description' => 'Needs power or cooling during periods of extreme heat.',
        ];
    }

    /** Link the equipment to a specific type, e.g. ->ofType($refrigerator). */
    public function ofType(TypeEquipement $type): static
    {
        return $this->state(['type_equipement_id' => $type->id]);
    }
}
