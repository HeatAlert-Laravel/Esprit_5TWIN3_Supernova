<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConseilVote extends Model
{
    protected $fillable = ['user_id', 'conseil_id', 'value'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }

    public function conseil(): BelongsTo
    {
        return $this->belongsTo(Conseil::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
