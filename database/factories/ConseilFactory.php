<?php

namespace Database\Factories;

use App\Models\CategorieConseil;
use App\Models\Conseil;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Conseil> */
class ConseilFactory extends Factory
{
    protected $model = Conseil::class;

    public function definition(): array
    {
        return [
            'categorie_conseil_id' => CategorieConseil::factory(),
            'titre' => fake()->sentence(6),
            'resume' => fake()->sentence(15),
            'contenu' => implode("\n\n", fake()->paragraphs(3)),
            'public_cible' => fake()->randomElement(array_keys(Conseil::AUDIENCES)),
            'situation' => fake()->randomElement(array_keys(Conseil::SITUATIONS)),
            'actif' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['actif' => true]);
    }
}
