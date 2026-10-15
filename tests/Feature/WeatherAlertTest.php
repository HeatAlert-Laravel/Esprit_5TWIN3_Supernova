<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('front office shows only published alerts and filters by neighborhood', function () {
    $publishedQuartier = Quartier::factory()->create(['nom' => 'Published area']);
    $hiddenQuartier = Quartier::factory()->create(['nom' => 'Hidden area']);
    AlerteMeteo::factory()->for($publishedQuartier)->create(['publiee' => true, 'titre' => 'Published heat']);
    AlerteMeteo::factory()->for($hiddenQuartier)->create(['publiee' => false, 'titre' => 'Draft heat']);

    $this->get(route('weather-alerts', ['quartier_id' => $publishedQuartier->id]))
        ->assertOk()
        ->assertSee('Published heat')
        ->assertDontSee('Draft heat');
});