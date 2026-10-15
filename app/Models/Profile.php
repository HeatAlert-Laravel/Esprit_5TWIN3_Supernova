<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'phone', 'address', 'neighborhood', 'has_fragile_person'];

    protected function casts(): array
    {
        return ['has_fragile_person' => 'boolean'];
    }

    /**
     * Display-only completeness rule (no schema change): a profile is "complete" when every
     * required household detail below holds a non-blank value. `has_fragile_person` is a
     * yes/no answer, so it is never counted as missing.
     */
    public const REQUIRED_DETAILS = ['phone', 'address', 'neighborhood'];

    /** @return list<string> Names of the required details that are blank. */
    public function missingDetails(): array
    {
        return array_values(array_filter(
            self::REQUIRED_DETAILS,
            fn (string $field): bool => trim((string) $this->getAttribute($field)) === '',
        ));
    }

    public function isComplete(): bool
    {
        return $this->missingDetails() === [];
    }

    /** Share of required details filled in, as a whole percentage (0-100). */
    public function completionPercent(): int
    {
        $total = count(self::REQUIRED_DETAILS);

        return (int) round(($total - count($this->missingDetails())) / $total * 100);
    }

    /** SQL condition that is true when a required detail is NULL or blank (SQL twin of isComplete()). */
    public static function incompleteSql(): string
    {
        return '('.implode(' or ', array_map(
            fn (string $field): string => "profiles.{$field} is null or trim(profiles.{$field}) = ''",
            self::REQUIRED_DETAILS,
        )).')';
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereRaw(self::incompleteSql());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sensitiveEquipments(): HasMany
    {
        return $this->hasMany(SensitiveEquipment::class);
    }
}
