<?php

use App\Models\Conseil;
use App\Models\ConseilVote;
use App\Models\User;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
});

function feedbackVotes(Conseil $article, array $values): void
{
    foreach ($values as $value) {
        ConseilVote::create(['conseil_id' => $article->id, 'user_id' => User::factory()->create()->id, 'value' => $value]);
    }
}

test('cards show real vote totals on home indexes saved and related advice without inline voting forms', function () {
    $resident = User::factory()->create();
    $article = Conseil::factory()->published()->create();
    $related = Conseil::factory()->published()->create(['categorie_conseil_id' => $article->categorie_conseil_id]);
    feedbackVotes($article, [1, 1, -1]);
    $resident->savedConseils()->attach($article);
    $this->actingAs($resident);
    foreach ([route('home'), route('advice'), route('advice', ['sort' => 'helpful']), route('advice.saved'), route('advice.show', $related)] as $url) {
        $this->get($url)->assertOk()->assertSee('Score 1 · 2 upvotes · 1 downvotes. Open article to vote.');
    }
    $this->get(route('advice'))->assertDontSee('data-vote-form', false);
});

test('helpful sorting ranks across categories by net score then upvotes and includes unrated above negative scores', function () {
    $negative = Conseil::factory()->published()->create(['titre' => 'A negative']);
    $unrated = Conseil::factory()->published()->create(['titre' => 'B unrated']);
    $mixed = Conseil::factory()->published()->create(['titre' => 'C mixed']);
    $winner = Conseil::factory()->published()->create(['titre' => 'D winner']);
    $draft = Conseil::factory()->create(['titre' => 'Hidden draft']);
    feedbackVotes($negative, [-1]);
    feedbackVotes($mixed, [1, 1, 1, -1, -1]);
    feedbackVotes($winner, [1, 1]);
    feedbackVotes($draft, [1, 1, 1]);
    $response = $this->get(route('advice', ['sort' => 'helpful']))->assertOk();
    expect($response->viewData('conseils')->pluck('id')->all())->toBe([$winner->id, $mixed->id, $unrated->id, $negative->id]);
    $response->assertSeeInOrder(['D winner', 'C mixed', 'B unrated', 'A negative'])
        ->assertDontSee('Hidden draft')->assertDontSee('ha-advice-category-head', false);
    $this->get(route('advice'))->assertOk()->assertSee('ha-advice-category-head', false);
});

test('helpful sorting keeps search audience situation saved membership and return navigation', function () {
    $resident = User::factory()->create();
    $match = Conseil::factory()->published()->create(['titre' => 'Heat preparation', 'public_cible' => 'everyone', 'situation' => 'both']);
    $other = Conseil::factory()->published()->create(['titre' => 'Heat unrelated', 'public_cible' => 'seniors', 'situation' => 'outage']);
    feedbackVotes($other, [1, 1]);
    $resident->savedConseils()->attach($match);
    $params = ['sort' => 'helpful', 'q' => 'Heat', 'audience' => 'parents', 'situation' => 'heatwave', 'category' => $match->categorie_conseil_id];
    $response = $this->actingAs($resident)->get(route('advice.saved', $params))->assertOk();
    expect($response->viewData('conseils')->pluck('id')->all())->toBe([$match->id]);
    $return = $response->viewData('returnTo');
    expect($return)->toContain('sort=helpful');
    $article = $this->get(route('advice.show', ['conseil' => $match, 'return' => $return]))->assertOk();
    expect($article->viewData('returnTo'))->toBe($return);
    $this->get(route('advice', ['sort' => 'untrusted']))->assertSessionHasErrors('sort');
});

test('helpful pagination retains filters and has stable ordering for tied scores', function () {
    $articles = Conseil::factory()->published()->count(14)->create(['titre' => 'Same title']);
    $response = $this->get(route('advice', ['sort' => 'helpful', 'page' => 2]))->assertOk();
    expect($response->viewData('conseils')->pluck('id')->all())->toBe($articles->skip(12)->pluck('id')->all());
    $response->assertSee('sort=helpful', false);
});

test('admin overview distinguishes positive feedback downvotes unrated and hidden drafts', function () {
    $helpful = Conseil::factory()->published()->create(['titre' => 'Helpful guidance']);
    $mixed = Conseil::factory()->published()->create(['titre' => 'Review guidance']);
    $unrated = Conseil::factory()->published()->create(['titre' => 'Unrated guidance']);
    $draft = Conseil::factory()->create(['titre' => 'Draft guidance']);
    feedbackVotes($helpful, [1, 1]);
    feedbackVotes($mixed, [1, -1]);
    feedbackVotes($draft, [-1]);
    $this->actingAs($this->admin);
    $response = $this->get(route('admin.conseils.index'))->assertOk()->assertSee('Reader feedback overview')->assertSee('Score 0');
    expect($response->viewData('mostHelpful')->pluck('id')->all())->toBe([$helpful->id])
        ->and($response->viewData('reviewFeedback')->pluck('id')->all())->toBe([$mixed->id]);
    $row = $response->viewData('conseils')->firstWhere('id', $mixed->id);
    expect($row->upvotes_count)->toBe(1)->and($row->downvotes_count)->toBe(1);
    foreach (['downvotes' => [$mixed->id], 'unrated' => [$unrated->id], 'helpful' => [$helpful->id, $mixed->id]] as $filter => $expected) {
        $response = $this->get(route('admin.conseils.index', ['feedback' => $filter]))->assertOk();
        expect($response->viewData('conseils')->pluck('id')->all())->toBe($expected);
    }
    $response = $this->get(route('admin.conseils.index', ['q' => 'Unrated']))->assertOk()->assertSee('No positive feedback yet.');
    expect($response->viewData('mostHelpful'))->toBeEmpty()->and($response->viewData('reviewFeedback'))->toBeEmpty();
    $this->get(route('admin.conseils.index', ['feedback' => 'untrusted']))->assertSessionHasErrors('feedback');
});
