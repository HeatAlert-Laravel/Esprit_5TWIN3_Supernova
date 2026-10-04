<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupure extends Model
{
    use HasFactory;

    public const TYPES = ['prévue', 'en cours'];

    public const STATUTS = ['active', 'résolue'];

    protected $fillable = ['quartier_id', 'type', 'statut', 'date_debut', 'date_fin_estimee', 'description'];

    protected function casts(): array
    {
        return ['date_debut' => 'datetime', 'date_fin_estimee' => 'datetime'];
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }
}
