<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membre 3 - entité ENFANT : un lieu frais, rattaché à un TypePoint et à un Quartier.
 */
class PointFraicheur extends Model
{
    use HasFactory;

    // Laravel devinerait "point_fraicheurs" tout seul ; on l'écrit pour être explicite.
    protected $table = 'point_fraicheurs';

    protected $fillable = [
        'type_point_id', 'quartier_id', 'nom', 'adresse',
        'latitude', 'longitude', 'horaires', 'accessible_pmr', 'actif', 'description',
    ];

    // MySQL renvoie 0/1 : les casts les transforment en vrais booléens PHP.
    protected function casts(): array
    {
        return ['accessible_pmr' => 'boolean', 'actif' => 'boolean'];
    }

    /** Un point appartient à un type. */
    public function typePoint(): BelongsTo
    {
        return $this->belongsTo(TypePoint::class);
    }

    /** Un point appartient à un quartier (entité du Membre 1). */
    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    /** Scope : PointFraicheur::actifs()->get() ne renvoie que les lieux visibles par les résidents. */
    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    /**
     * Determines whether the location is currently open based on its horaires string.
     */
    public function getOpenStatus(): array
    {
        $hours = trim($this->horaires ?? '');
        if ($hours === '' || strtolower($hours) === '24/7' || strtolower($hours) === 'always open') {
            return ['is_open' => true, 'label' => 'OPEN', 'detail' => 'Open 24/7'];
        }

        if (preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $hours, $m)) {
            $now = now();
            $start = now()->copy()->setTime((int)$m[1], (int)$m[2], 0);
            $end = now()->copy()->setTime((int)$m[3], (int)$m[4], 0);

            if ($now->between($start, $end)) {
                return ['is_open' => true, 'label' => 'OPEN', 'detail' => 'Closes ' . sprintf('%02d:%02d', $m[3], $m[4])];
            } else {
                return ['is_open' => false, 'label' => 'CLOSED', 'detail' => 'Opens ' . sprintf('%02d:%02d', $m[1], $m[2])];
            }
        }

        return ['is_open' => true, 'label' => 'OPEN', 'detail' => $hours];
    }

    /**
     * Returns relevant amenity indicators for the card.
     */
    public function getAmenities(): array
    {
        $amenities = [];
        $typeName = strtolower($this->typePoint?->nom ?? '');
        $text = strtolower(($this->description ?? '') . ' ' . $this->nom . ' ' . $typeName);

        if (str_contains($text, 'fountain') || str_contains($text, 'water') || str_contains($text, 'mister') || str_contains($text, 'drinking') || str_contains($typeName, 'fountain')) {
            $amenities[] = ['icon' => '💧', 'label' => 'Water'];
        }
        if (str_contains($text, 'shade') || str_contains($text, 'park') || str_contains($text, 'tree') || str_contains($text, 'garden')) {
            $amenities[] = ['icon' => '🌳', 'label' => 'Shade'];
        }
        if (str_contains($text, 'pool') || str_contains($text, 'swim')) {
            $amenities[] = ['icon' => '🛟', 'label' => 'Lifeguard'];
        }
        if (str_contains($text, 'air-conditioned') || str_contains($text, 'climate') || str_contains($text, 'ac') || str_contains($text, 'library') || str_contains($text, 'hall')) {
            $amenities[] = ['icon' => '❄️', 'label' => 'Indoor AC'];
        }
        if ($this->accessible_pmr) {
            $amenities[] = ['icon' => '♿', 'label' => 'PRM Access'];
        }

        return $amenities;
    }
}
