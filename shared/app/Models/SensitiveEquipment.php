<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensitiveEquipment extends Model
{
    use HasFactory;

    protected $table = 'sensitive_equipments';

    /**
     * Type, heat/outage sensitivity and risk are NOT stored here: they live on TypeEquipement.
     */
    protected $fillable = ['profile_id', 'type_equipement_id', 'name', 'description'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function typeEquipement(): BelongsTo
    {
        return $this->belongsTo(TypeEquipement::class);
    }

    /** Equipment whose type is rated high or critical risk. */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->whereHas('typeEquipement', fn (Builder $type) => $type->whereIn('risk_level', TypeEquipement::HIGH_RISK_LEVELS));
    }

    /**
     * Simple rule-based, informational guidance derived from the type (no AI, no medical advice,
     * no guarantee of safety). Requires the typeEquipement relation (eager load it in lists).
     *
     * @return list<string>
     */
    public function preparednessMessages(): array
    {
        $messages = [];

        if ($this->typeEquipement?->sensitive_to_outage) {
            $messages[] = 'This equipment depends on electricity. Consider a backup power plan.';
        }

        if ($this->typeEquipement?->sensitive_to_heat) {
            $messages[] = 'This equipment may require protection during high temperatures.';
        }

        return $messages;
    }
}
