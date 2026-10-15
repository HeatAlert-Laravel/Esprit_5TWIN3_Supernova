<?php

use App\Models\CategorieConseil;
use App\Models\Conseil;
use App\Models\User;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
});

test('guests can browse advice grouped by category but never see drafts or empty categories', function () {
    $category = CategorieConseil::factory()->create(['nom' => 'Visible advice topic']);
    $article = Conseil::factory()->for($category, 'categorieConseil')->published()->create(['titre' => 'Public preparation article']);
    $draft = Conseil::factory()->create(['titre' => 'Private draft title']);
    $response = $this->get(route('advice'))->assertOk()->assertSee($category->nom)->assertSee($article->titre)
        ->assertDontSee($draft->titre)->assertDontSee($draft->categorieConseil->nom);
    expect($response->viewData('conseils')->total())->toBe(1);
    $reading = $this->get(route('advice.show', $article))->assertOk();
    foreach ($article->paragraphs() as $paragraph) {
        $reading->assertSee($paragraph);
    }
    $this->get(route('advice.show', $draft))->assertNotFound();
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->get(route('advice.show', $draft))->assertNotFound();
});

test('preview requires login and an administrator and can display a draft', function () {
    $draft = Conseil::factory()->create();
    $this->get(route('advice.preview', $draft))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => 'USER']))->get(route('advice.preview', $draft))->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))->get(route('advice.preview', $draft))
        ->assertOk()->assertSee('Administrator preview')->assertSee('Residents cannot see it yet.')->assertSee($draft->titre);
});

test('audience and situation filters include general advice while excluding unrelated articles', function () {
    $category = CategorieConseil::factory()->create();
    $base = ['categorie_conseil_id' => $category->id];
    $general = Conseil::factory()->published()->create($base + ['titre' => 'General useful advice', 'public_cible' => 'everyone', 'situation' => 'both']);
    $specific = Conseil::factory()->published()->create($base + ['titre' => 'Senior heat advice', 'public_cible' => 'seniors', 'situation' => 'heatwave']);
    $wrongAudience = Conseil::factory()->published()->create($base + ['titre' => 'Parents only heat advice', 'public_cible' => 'parents', 'situation' => 'heatwave']);
    $wrongSituation = Conseil::factory()->published()->create($base + ['titre' => 'Senior outage advice', 'public_cible' => 'seniors', 'situation' => 'outage']);
    $draft = Conseil::factory()->create($base + ['titre' => 'Secret general advice']);
    $response = $this->get(route('advice', ['audience' => 'seniors', 'situation' => 'heatwave', 'category' => $category->id]))
        ->assertOk()->assertSee($general->titre)->assertSee($specific->titre)
        ->assertDontSee($wrongAudience->titre)->assertDontSee($wrongSituation->titre)->assertDontSee($draft->titre);
    expect($response->viewData('conseils')->total())->toBe(2);
    $this->get(route('advice', ['situation' => 'outage']))->assertOk()->assertSee($wrongSituation->titre)->assertSee($general->titre)->assertDontSee($specific->titre);
});

test('search includes full text and composes with category and audience filters', function () {
    $category = CategorieConseil::factory()->create();
    $matching = Conseil::factory()->published()->create(['categorie_conseil_id' => $category->id, 'contenu' => 'Find your torch before dark.', 'public_cible' => 'everyone']);
    $excluded = Conseil::factory()->published()->create(['titre' => 'Another torch article', 'public_cible' => 'everyone']);
    $response = $this->get(route('advice', ['q' => 'torch', 'category' => $category->id, 'audience' => 'parents']))->assertOk()
        ->assertSee($matching->titre)->assertDontSee($excluded->titre);
    expect($response->viewData('conseils')->total())->toBe(1);
    $this->get(route('advice', ['q' => 'no-such-article']))->assertOk()->assertSee('No advice found just yet');
});

test('reading pages escape pasted html and show only published related advice from the same category', function () {
    $category = CategorieConseil::factory()->create();
    $article = Conseil::factory()->published()->create(['categorie_conseil_id' => $category->id, 'contenu' => "<script>alert('x')</script>\n\nSecond paragraph."]);
    $related = Conseil::factory()->published()->create(['categorie_conseil_id' => $category->id, 'titre' => 'Related public article']);
    $draft = Conseil::factory()->create(['categorie_conseil_id' => $category->id, 'titre' => 'Related private article']);
    $other = Conseil::factory()->published()->create(['titre' => 'Unrelated category article']);
    $this->get(route('advice.show', $article))->assertOk()->assertSee('<script>alert', true)
        ->assertDontSee("<script>alert('x')</script>", false)->assertSee('Second paragraph.')->assertSee($related->titre)
        ->assertDontSee($draft->titre)->assertDontSee($other->titre);
    expect($article->readingMinutes())->toBe(1);
    $article->contenu = implode(' ', array_fill(0, 201, 'word'));
    expect($article->readingMinutes())->toBe(2);
});

test('public advice pagination retains active filters', function () {
    $category = CategorieConseil::factory()->create();
    Conseil::factory()->count(13)->published()->create(['categorie_conseil_id' => $category->id, 'titre' => 'Torch preparation', 'public_cible' => 'everyone', 'situation' => 'both']);
    $params = ['q' => 'Torch', 'category' => $category->id, 'audience' => 'parents', 'situation' => 'outage'];
    $response = $this->get(route('advice', $params))->assertOk();
    expect($response->viewData('conseils')->total())->toBe(13);
    foreach (['q=Torch', 'category='.$category->id, 'audience=parents', 'situation=outage', 'page=2'] as $query) {
        $response->assertSee($query, false);
    }
    $next = $this->get(route('advice', $params + ['page' => 2]))->assertOk();
    expect($next->viewData('conseils')->count())->toBe(1);
});

test('invalid filter parameters are rejected', function () {
    $this->getJson(route('advice', ['category' => 999999]))->assertUnprocessable()->assertJsonValidationErrors('category');
    $this->getJson(route('advice', ['audience' => 'unknown']))->assertUnprocessable()->assertJsonValidationErrors('audience');
    $this->getJson(route('advice', ['situation' => 'both']))->assertUnprocessable()->assertJsonValidationErrors('situation');
    $this->getJson(route('advice', ['q' => str_repeat('a', 101)]))->assertUnprocessable()->assertJsonValidationErrors('q');
});
