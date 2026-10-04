<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlerteMeteo extends Model
{
    use HasFactory;

    protected $fillable = [
        'quartier_id', 'titre', 'niveau', 'temperature_max',
        'date_debut', 'date_fin', 'publiee',
    ];

    protected function casts(): array
    {
        return [
            'temperature_max' => 'float',
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
}