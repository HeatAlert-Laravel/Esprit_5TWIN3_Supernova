<?php

namespace Database\Factories;

use App\Models\TypeEquipement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * The seven canonical types are created by the migration / TypeEquipementSeeder. This factory
 * therefore draws from additional realistic device kinds (unique names) to avoid clashing with them.
 *
 * @extends Factory<TypeEquipement>
 */
class TypeEquipementFactory extends Factory
{
    protected $model = TypeEquipement::class;

    /** @var list<array{string, bool, bool, string}> name, heat, outage, risk */
    private const EXTRA_TYPES = [
        ['Oxygen concentrator', true, true, 'critical'],
        ['Insulin cooler', true, true, 'critical'],
        ['Wine cellar', true, true, 'low'],
        ['Dehumidifier', false, true, 'low'],
        ['Heated terrarium', true, true, 'medium'],
        ['Water pump', false, true, 'medium'],
        ['Home server', true, true, 'low'],
        ['Electric wheelchair charger', false, true, 'high'],
        ['Baby milk warmer', false, true, 'low'],
        ['Cold storage cabinet', true, true, 'high'],
    ];

    public function definition(): array
    {
        [$name, $heat, $outage, $risk] = fake()->unique()->randomElement(self::EXTRA_TYPES);

        return [
            'name' => $name,
            'sensitive_to_heat' => $heat,
            'sensitive_to_outage' => $outage,
            'risk_level' => $risk,
        ];
    }

    public function risk(string $level): static
    {
        return $this->state(['risk_level' => $level]);
    }
}
