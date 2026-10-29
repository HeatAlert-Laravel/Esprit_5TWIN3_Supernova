<?php

use App\Models\Conseil;
use App\Models\User;

beforeEach(fn () => $this->artisan('migrate', ['--force' => true]));

test('home features three published articles with general advice first and excludes drafts', function () {
    $general = Conseil::factory()->published()->create([
        'titre' => 'A plan for every household', 'public_cible' => 'everyone', 'updated_at' => '2026-01-01',
    ]);
    $older = Conseil::factory()->published()->create([
        'titre' => 'An older family article', 'public_cible' => 'parents', 'updated_at' => '2026-01-02',
    ]);
    $recent = Conseil::factory()->published()->create([
        'titre' => 'A recent family article', 'public_cible' => 'parents', 'updated_at' => '2026-01-03',
    ]);
    $newest = Conseil::factory()->published()->create([
        'titre' => 'The newest caregiver article', 'public_cible' => 'caregivers', 'updated_at' => '2026-01-04',
    ]);
    $draft = Conseil::factory()->create(['titre' => 'An unpublished private article', 'updated_at' => '2026-01-05']);

    $response = $this->get(route('home'))->assertOk()
        ->assertSeeInOrder([$general->titre, $newest->titre, $recent->titre])
        ->assertSee($general->resume)->assertSee(route('advice.show', ['conseil' => $general, 'return' => '/advice']), false)
        ->assertDontSee($older->titre)->assertDontSee($draft->titre);
    expect($response->viewData('featuredAdvice')->pluck('id')->all())->toBe([$general->id, $newest->id, $recent->id]);

    $newest->update(['actif' => false]);
    $this->get(route('home'))->assertOk()->assertDontSee($newest->titre)->assertSee($older->titre);
});

test('home keeps bookmarked articles personal to the signed in user', function () {
    $article = Conseil::factory()->published()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->savedConseils()->attach($article);

    $response = $this->actingAs($owner)->get(route('home'))->assertOk();
    expect($response->viewData('featuredAdvice')->first()->is_saved)->toBeTrue();
    $response->assertSee('Remove '.$article->titre.' from saved advice');

    $response = $this->actingAs($other)->get(route('home'))->assertOk();
    expect($response->viewData('featuredAdvice')->first()->is_saved)->toBeFalse();
    $response->assertSee('aria-label="Save '.$article->titre.'"', false);
});

test('home gives a helpful empty state before any advice is published', function () {
    $draft = Conseil::factory()->create(['titre' => 'Private draft']);
    $this->get(route('home'))->assertOk()->assertSee('Advice is on its way')
        ->assertSee('View outages')->assertSee('Find cooling points')->assertDontSee($draft->titre);
});
