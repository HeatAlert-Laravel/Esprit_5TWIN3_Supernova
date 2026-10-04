<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conseil extends Model
{
    use HasFactory;

    public const AUDIENCES = [
        'everyone' => 'Everyone',
        'seniors' => 'Seniors',
        'parents' => 'Parents & families',
        'caregivers' => 'Caregivers',
    ];

    public const SITUATIONS = [
        'heatwave' => 'Heatwave',
        'outage' => 'Power outage',
        'both' => 'Heatwave & power outage',
    ];

    protected $fillable = ['categorie_conseil_id', 'titre', 'resume', 'contenu', 'public_cible', 'situation', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function categorieConseil(): BelongsTo
    {
        return $this->belongsTo(CategorieConseil::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function audienceLabel(): string
    {
        return __(self::AUDIENCES[$this->public_cible] ?? 'Everyone');
    }

    public function situationLabel(): string
    {
        return __(self::SITUATIONS[$this->situation] ?? 'Heatwave & power outage');
    }

    public function situationIcon(): string
    {
        return match ($this->situation) {
            'heatwave' => 'sun',
            'outage' => 'zap',
            default => 'shield-check',
        };
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(count(preg_split('/\s+/u', trim($this->contenu), -1, PREG_SPLIT_NO_EMPTY)) / 200));
    }

    /** Plain text only. Blade escapes every paragraph, including any pasted HTML. */
    public function paragraphs(): array
    {
        return preg_split('/\R\s*\R/u', trim($this->contenu), -1, PREG_SPLIT_NO_EMPTY);
    }
}
