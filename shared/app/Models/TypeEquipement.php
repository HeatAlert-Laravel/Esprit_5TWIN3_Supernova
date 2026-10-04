<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Parent entity of the Module 5 pair: TypeEquipement 1 -> N SensitiveEquipment.
 *
 * It is the single source of truth for what kind of device something is and how
 * sensitive/risky it is, so SensitiveEquipment never stores those values itself.
 */
class TypeEquipement extends Model
{
    use HasFactory;

    protected $table = 'type_equipements';

    /** One controlled risk vocabulary, ordered from least to most severe. */
    public const RISK_LEVELS = ['low', 'medium', 'high', 'critical'];

    /** Risk levels counted as "high risk" on dashboards and in the check-first rule. */
    public const HIGH_RISK_LEVELS = ['high', 'critical'];

    /** Badge colour for each risk level (the single mapping used by every view). */
    private const RISK_VARIANTS = ['critical' => 'critical', 'high' => 'danger', 'medium' => 'warning', 'low' => 'cool'];

    public static function riskVariant(?string $level): string
    {
        return self::RISK_VARIANTS[$level] ?? 'neutral';
    }

    protected $fillable = ['name', 'sensitive_to_heat', 'sensitive_to_outage', 'risk_level'];

    protected function casts(): array
    {
        return ['sensitive_to_heat' => 'boolean', 'sensitive_to_outage' => 'boolean'];
    }

    public function sensitiveEquipments(): HasMany
    {
        return $this->hasMany(SensitiveEquipment::class);
    }
}
