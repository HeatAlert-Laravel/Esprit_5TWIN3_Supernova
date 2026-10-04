<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieConseil extends Model
{
    use HasFactory;

    public const ICONS = [
        'lightbulb' => 'Light bulb',
        'sun' => 'Sun',
        'zap' => 'Electricity',
        'plug' => 'Equipment',
        'heart-pulse' => 'Care',
        'users' => 'People',
        'shield-check' => 'Protection',
    ];

    protected $table = 'categorie_conseils';

    protected $fillable = ['nom', 'description', 'icone'];

    public function conseils(): HasMany
    {
        return $this->hasMany(Conseil::class);
    }
}
