<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Membre 3 - entité PARENTE : TypePoint 1 -> N PointFraicheur.
 */
class TypePoint extends Model
{
    use HasFactory;

    // Colonnes autorisées pour TypePoint::create([...]) / ->update([...]) (protection mass assignment).
    protected $fillable = ['nom', 'icone', 'description'];

    /** Available icons supported by <x-cooling-icon>. */
    public const ICONS = ['tree', 'droplet', 'snowflake', 'waves', 'book', 'sun', 'home', 'building'];

    /** Un type possède plusieurs points de fraîcheur. */
    public function pointFraicheurs(): HasMany
    {
        return $this->hasMany(PointFraicheur::class);
    }
}
