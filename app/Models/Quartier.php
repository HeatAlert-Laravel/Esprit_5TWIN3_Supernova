<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quartier extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ville', 'gouvernorat', 'code_postal', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function alerteMeteos(): HasMany
    {
        return $this->hasMany(AlerteMeteo::class);
    }

    /** Membre 3 : un quartier possède plusieurs points de fraîcheur. */
    public function pointFraicheurs(): HasMany
    {
        return $this->hasMany(PointFraicheur::class);
    }
}