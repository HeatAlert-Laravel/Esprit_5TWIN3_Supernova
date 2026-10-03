<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quartier extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'ville', 'code_postal'];

    public function alerteMeteos(): HasMany
    {
        return $this->hasMany(AlerteMeteo::class);
    }
}