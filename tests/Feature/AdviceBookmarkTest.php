<?php

use App\Models\AdviceDocument;
use App\Models\Conseil;
use App\Models\User;
use App\Support\AdviceNavigation;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->resident = User::factory()->create(['role' => 'USER']);
    $this->article = Conseil::factory()->published()->create(['public_cible' => 'everyone', 'situation' => 'both']);
});

test('bookmarks and the saved advice page require login and a sign-in prompt never saves through GET', function () {
    $this->get(route('advice.saved'))->assertRedirect(route('login'));
    $this->get(route('advice.save-prompt', $this->article))->assertRedirect(route('login'));
    $this->put(route('advice.bookmark.store', $this->article))->assertRedirect(route('login'));
    $this->delete(route('advice.bookmark.destroy', $this->article))->assertRedirect(route('login'));
    $this->actingAs($this->resident)->get(route('advice.save-prompt', $this->article))->assertOk()->assertSee('Save article');
    expect(DB::table('conseil_user')->count())->toBe(0);
});

test('saving is idempotent and removing a bookmark affects only the current user', function () {
    $other = User::factory()->create();
    $other->savedConseils()->attach($this->article);
    $this->actingAs($this->resident);
    for ($i = 0; $i < 2; $i++) {
        $this->put(route('advice.bookmark.store', $this->article))->assertRedirect(route('advice'));
    }
    expect($this->resident->savedConseils()->count())->toBe(1)->and(DB::table('conseil_user')->count())->toBe(2);
    $this->get(route('advice.saved'))->assertOk()->assertSee($this->article->titre)->assertSee('1 article');
    $this->delete(route('advice.bookmark.destroy', $this->article))->assertRedirect(route('advice'));
    expect($this->resident->savedConseils()->count())->toBe(0)->and($other->savedConseils()->count())->toBe(1);
    $this->get(route('advice.saved'))->assertOk()->assertSee('No saved advice yet')->assertSee('0 articles');
});

test('AJAX bookmark responses report the explicit saved state', function () {
    $this->actingAs($this->resident)->putJson(route('advice.bookmark.store', $this->article))->assertOk()->assertJsonPath('saved', true);
    $this->get(route('advice'))->assertOk()->assertSee('aria-pressed="true"', false);
    $this->deleteJson(route('advice.bookmark.destroy', $this->article))->assertOk()->assertJsonPath('saved', false);
});

test('saved lists are private and hide advice that has been moved to draft', function () {
    $this->resident->savedConseils()->attach($this->article);
    $otherArticle = Conseil::factory()->published()->create(['titre' => 'Another users private bookmark']);
    User::factory()->create()->savedConseils()->attach($otherArticle);
    $this->actingAs($this->resident)->get(route('advice.saved'))->assertOk()->assertSee($this->article->titre)->assertDontSee($otherArticle->titre);
    $this->article->update(['actif' => false]);
    $this->get(route('advice.saved'))->assertOk()->assertDontSee($this->article->titre)->assertSee('No saved advice yet');
    $this->put(route('advice.bookmark.store', $this->article))->assertNotFound();
    $this->get(route('advice.show', $this->article))->assertNotFound();
});

test('bookmarks cascade away when their user or article is deleted', function () {
    $this->resident->savedConseils()->attach($this->article);
    $this->article->delete();
    expect(DB::table('conseil_user')->count())->toBe(0);
    $article = Conseil::factory()->published()->create();
    $this->resident->savedConseils()->attach($article);
    $this->resident->delete();
    expect(DB::table('conseil_user')->count())->toBe(0)->and($article->fresh())->not->toBeNull();
});

test('search and pagination context survives reading and bookmarking an article', function () {
    $params = ['q' => 'Plan', 'category' => $this->article->categorie_conseil_id, 'audience' => 'parents', 'situation' => 'outage', 'page' => 2];
    $this->article->update(['titre' => 'Plan for an outage']);
    Conseil::factory()->count(12)->published()->create([
        'categorie_conseil_id' => $this->article->categorie_conseil_id, 'titre' => 'Plan another step',
        'public_cible' => 'everyone', 'situation' => 'both',
    ]);
    $return = '/advice?'.http_build_query($params);
    $index = $this->actingAs($this->resident)->get(route('advice', $params))->assertOk()->assertSee('13 articles');
    expect($index->viewData('returnTo'))->toBe($return);
    $this->get(route('advice.show', ['conseil' => $this->article, 'return' => $return]))
        ->assertOk()->assertSee('Back to results')->assertSee(e(route('advice', $params)), false);
    $this->put(route('advice.bookmark.store', $this->article), ['return' => $return])->assertRedirect(route('advice', $params));
    $this->delete(route('advice.bookmark.destroy', $this->article), ['return' => $return, 'article' => '1'])
        ->assertRedirect(route('advice.show', ['conseil' => $this->article, 'return' => $return]));
});

test('return destinations cannot redirect a resident to another site', function () {
    foreach (['https://example.test/advice', '//example.test/advice', '/login', '/advice?category[]=1', '/advice?situation=unknown'] as $bad) {
        expect(AdviceNavigation::returnUrl($bad))->toBe(route('advice'));
        $this->actingAs($this->resident)->put(route('advice.bookmark.store', $this->article), ['return' => $bad])
            ->assertRedirect(route('advice'));
    }
});

test('saved results retain filters and recover when removing the last item on a later page', function () {
    $articles = Conseil::factory()->count(13)->published()->create(['titre' => 'Saved torch plan', 'public_cible' => 'everyone', 'situation' => 'outage']);
    $this->resident->savedConseils()->attach($articles->modelKeys());
    $params = ['q' => 'torch', 'audience' => 'parents', 'situation' => 'outage', 'page' => 2];
    $response = $this->actingAs($this->resident)->get(route('advice.saved', $params))->assertOk()->assertSee('13 articles');
    $last = $response->viewData('conseils')->first();
    $this->delete(route('advice.bookmark.destroy', $last), ['return' => '/advice/saved?'.http_build_query($params)])->assertRedirect(route('advice.saved', $params));
    $this->get(route('advice.saved', $params))->assertRedirect(route('advice.saved', array_merge($params, ['page' => 1])));
});

test('formatted resident content exactly matches the admin renderer and remains searchable', function () {
    $document = ['ops' => [
        ['insert' => 'A useful section'], ['insert' => "\n", 'attributes' => ['header' => 2]],
        ['insert' => 'Keep your torch'], ['insert' => "\n", 'attributes' => ['list' => 'ordered']],
        ['insert' => 'Ready', 'attributes' => ['bold' => true, 'italic' => true]], ['insert' => "\n"],
    ]];
    $this->article->update(['contenu_formate' => $document]);
    $this->get(route('advice.show', $this->article))->assertOk()->assertSee(AdviceDocument::html($document), false);
    $this->get(route('advice', ['q' => 'torch']))->assertOk()->assertSee($this->article->titre);
});
