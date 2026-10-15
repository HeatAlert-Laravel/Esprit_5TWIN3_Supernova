<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    use HasFactory;

    public const STATUTS = ['en attente', 'validé', 'rejeté'];

    protected $fillable = ['coupure_id', 'user_id', 'adresse', 'description', 'statut'];

    public function coupure(): BelongsTo
    {
        return $this->belongsTo(Coupure::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
