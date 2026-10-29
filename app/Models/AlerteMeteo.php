<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
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

    /** Vigilance levels from the calmest to the most severe. */
    public const LEVELS = ['vert', 'jaune', 'orange', 'rouge'];

    protected $fillable = [
        'quartier_id', 'titre', 'type', 'niveau', 'temperature_max',
        'temperature_min', 'temperature_ressentie', 'humidite', 'indice_uv',
        'vitesse_vent', 'risque_coupure', 'source', 'generee_auto',
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
            'generee_auto' => 'boolean',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'publiee' => 'boolean',
        ];
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publiee', true);
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('generee_auto', true);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('generee_auto', false);
    }

    /** Alerts whose period includes the given day (today by default). */
    public function scopeCurrent(Builder $query, ?Carbon $date = null): Builder
    {
        $day = ($date ?? today())->toDateString();

        return $query->whereDate('date_debut', '<=', $day)->whereDate('date_fin', '>=', $day);
    }

    /** Most severe level first (rouge, orange, jaune, vert). */
    public function scopeMostSevereFirst(Builder $query): Builder
    {
        return $query->orderByRaw("CASE niveau WHEN 'rouge' THEN 0 WHEN 'orange' THEN 1 WHEN 'jaune' THEN 2 ELSE 3 END");
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
        return self::badgeVariantFor($this->niveau);
    }

    public static function badgeVariantFor(?string $level): string
    {
        return match ($level) {
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
        return self::labelFor($this->niveau);
    }

    public static function labelFor(?string $level): string
    {
        return match ($level) {
            'vert' => 'Green',
            'jaune' => 'Yellow',
            'orange' => 'Orange',
            'rouge' => 'Red',
            default => 'Notice',
        };
    }

    public function levelAdvice(): string
    {
        return self::adviceFor($this->niveau);
    }

    public static function adviceFor(?string $level): string
    {
        return match ($level) {
            'vert' => 'Stay hydrated and keep an eye on vulnerable people.',
            'jaune' => 'Limit outdoor activity during the hottest hours.',
            'orange' => 'Postpone strenuous activities and stay in the shade.',
            'rouge' => 'Avoid going out, keep your home cool and check on others.',
            default => 'Follow local safety advice.',
        };
    } 
}