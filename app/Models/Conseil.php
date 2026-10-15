<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    protected $fillable = ['categorie_conseil_id', 'titre', 'resume', 'contenu', 'contenu_formate', 'public_cible', 'situation', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'contenu_formate' => 'array', 'is_saved' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Conseil $conseil) {
            if ($conseil->contenu_formate !== null) {
                $document = AdviceDocument::normalize($conseil->contenu_formate);
                $conseil->contenu_formate = $document;
                $conseil->contenu = AdviceDocument::text($document);
            }
        });
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function bodyHtml(): string
    {
        if ($this->contenu_formate !== null) {
            return AdviceDocument::html($this->contenu_formate);
        }

        return implode('', array_map(fn ($paragraph) => '<p>'.e($paragraph).'</p>', $this->paragraphs()));
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
