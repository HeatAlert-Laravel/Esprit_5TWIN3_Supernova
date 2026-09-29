<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sensitiveEquipments(): HasMany
    {
        return $this->hasMany(SensitiveEquipment::class);
    }
}
