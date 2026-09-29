<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensitiveEquipment extends Model
{
    use HasFactory;

    protected $table = 'sensitive_equipments';

    protected $fillable = ['profile_id', 'name', 'type', 'description', 'priority_level'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
