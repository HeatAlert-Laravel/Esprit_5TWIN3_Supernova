<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlerteMeteo extends Model
{
    use HasFactory;

    public const TYPES = [
        'canicule' => 'Heatwave',
        'vague_de_chaleur' => 'Heat spell',
        'orage' => 'Storm',
        'secheresse' => 'Drought',
        'autre' => 'Other',
    ];

    protected $fillable = [
        'quartier_id', 'titre', 'type', 'niveau', 'temperature_max',
        'temperature_min', 'temperature_ressentie', 'humidite', 'indice_uv',
        'vitesse_vent', 'risque_coupure', 'source',
        'date_debut', 'date_fin', 'publiee',
    ];

    protected function casts(): array
    {
        return [
            'temperature_max' => 'float',
            'temperature_min' => 'float',
            'temperature_ressentie' => 'float',
            'humidite' => 'integer',
            'indice_uv' => 'integer',
            'vitesse_vent' => 'float',
            'risque_coupure' => 'boolean',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'publiee' => 'boolean',
        ];
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function temporalStatus(?Carbon $date = null): string
    {
        $date ??= today();

        return match (true) {
            $date->lt($this->date_debut) => 'upcoming',
            $date->gt($this->date_fin) => 'expired',
            default => 'current',
        };
    }

    public function levelBadgeVariant(): string
    {
        return match ($this->niveau) {
            'vert' => 'level-green',
            'jaune' => 'level-yellow',
            'orange' => 'level-orange',
            'rouge' => 'level-red',
            default => 'neutral',
        };
    }

    public function temporalStatusBadgeVariant(): string
    {
        return match ($this->temporalStatus()) {
            'current' => 'success',
            'upcoming' => 'info',
            'expired' => 'neutral',
            default => 'neutral',
        };
    }

    public function temporalStatusLabel(): string
    {
        return match ($this->temporalStatus()) {
            'current' => 'Active now',
            'upcoming' => 'Upcoming',
            'expired' => 'Ended',
            default => 'Unknown',
        };
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Other';
    }

    public function levelLabel(): string
    {
        return match ($this->niveau) {
            'vert' => 'Green',
            'jaune' => 'Yellow',
            'orange' => 'Orange',
            'rouge' => 'Red',
            default => 'Notice',
        };
    }

    public function levelAdvice(): string
    {
        return match ($this->niveau) {
            'vert' => 'Stay hydrated and keep an eye on vulnerable people.',
            'jaune' => 'Limit outdoor activity during the hottest hours.',
            'orange' => 'Postpone strenuous activities and stay in the shade.',
            'rouge' => 'Avoid going out, keep your home cool and check on others.',
            default => 'Follow local safety advice.',
        };
    } 
}