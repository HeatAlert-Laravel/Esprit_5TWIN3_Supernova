<?php

use App\Models\Conseil;
use App\Models\ConseilVote;
use App\Models\User;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->article = Conseil::factory()->published()->create();
    $this->user = User::factory()->create();
});

test('guests see the score but must sign in to vote and the sign in destination never casts a vote', function () {
    $this->get(route('advice.show', $this->article))->assertOk()->assertSee('Was this advice useful?')
        ->assertSee('Sign in to upvote')->assertDontSee('data-vote-form', false);
    $this->putJson(route('advice.vote.update', $this->article), ['value' => 1])->assertUnauthorized();
    $this->get(route('advice.vote.prompt', $this->article))->assertRedirect(route('login'));
    $this->actingAs($this->user)->get(route('advice.vote.prompt', $this->article))->assertOk();
    expect(ConseilVote::count())->toBe(0);
});

test('votes are explicit idempotent states that can switch direction and be removed', function () {
    $this->actingAs($this->user);
    $route = route('advice.vote.update', $this->article);
    foreach ([1, 1, -1, -1, 0, 0] as $value) {
        $this->putJson($route, ['value' => $value])->assertOk()->assertJsonPath('vote', $value)
            ->assertJsonPath('score', $value)
            ->assertJsonPath('upvotes', $value === 1 ? 1 : 0)->assertJsonPath('downvotes', $value === -1 ? 1 : 0);
        expect(ConseilVote::count())->toBe($value === 0 ? 0 : 1);
    }
});

test('each account owns its own vote and cannot overwrite or remove another account vote', function () {
    $other = User::factory()->create();
    ConseilVote::create(['user_id' => $other->id, 'conseil_id' => $this->article->id, 'value' => -1]);
    $this->actingAs($this->user);
    $this->putJson(route('advice.vote.update', $this->article), [
        'value' => 1, 'user_id' => $other->id, 'conseil_id' => 999999,
    ])->assertOk()->assertJsonPath('score', 0)->assertJsonPath('upvotes', 1)->assertJsonPath('downvotes', 1);
    expect($this->user->conseilVotes()->first()->value)->toBe(1);
    $this->putJson(route('advice.vote.update', $this->article), ['value' => 0, 'user_id' => $other->id])
        ->assertOk()->assertJsonPath('score', -1);
    expect(ConseilVote::firstOrFail()->user_id)->toBe($other->id);
});

test('invalid vote values are rejected without changing the existing vote', function () {
    $this->actingAs($this->user);
    ConseilVote::create(['user_id' => $this->user->id, 'conseil_id' => $this->article->id, 'value' => 1]);
    foreach ([[], ['value' => 2], ['value' => -2], ['value' => 'up'], ['value' => [1]], ['value' => 0.5]] as $data) {
        $this->putJson(route('advice.vote.update', $this->article), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('value');
    }
    expect(ConseilVote::firstOrFail()->value)->toBe(1);
});

test('drafts reject votes from residents and administrators', function () {
    $this->article->update(['actif' => false]);
    foreach ([$this->user, User::factory()->create(['role' => 'ADMIN'])] as $user) {
        $this->actingAs($user)->putJson(route('advice.vote.update', $this->article), ['value' => 1])->assertNotFound();
    }
    expect(ConseilVote::count())->toBe(0);
});

test('votes persist across article visits and HTML submissions preserve safe filter context', function () {
    $this->actingAs($this->user)->put(route('advice.vote.update', $this->article), [
        'value' => 1, 'return' => '/advice?situation=heatwave',
    ])->assertRedirect(route('advice.show', ['conseil' => $this->article, 'return' => '/advice?situation=heatwave']).'#article-feedback');
    $this->get(route('advice.show', $this->article))->assertOk()->assertViewHas('currentVote', 1)
        ->assertSee('data-vote="1"', false)->assertSee('value="0" class="ha-vote-button ha-vote-button--up"', false);
    $this->put(route('advice.vote.update', $this->article), ['value' => 0, 'return' => 'https://example.test'])
        ->assertRedirect(route('advice.show', ['conseil' => $this->article, 'return' => '/advice']).'#article-feedback');
});

test('admin sees separate totals while resident preview never offers voting', function () {
    ConseilVote::create(['user_id' => $this->user->id, 'conseil_id' => $this->article->id, 'value' => 1]);
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $response = $this->actingAs($admin)->get(route('admin.conseils.show', $this->article))->assertOk()
        ->assertSee('Reader feedback')->assertSee('Upvotes')->assertSee('Downvotes');
    expect($response->viewData('conseil')->upvotes_count)->toBe(1)
        ->and($response->viewData('conseil')->downvotes_count)->toBe(0);
    $this->get(route('advice.preview', $this->article))->assertOk()->assertDontSee('data-advice-votes', false);
    $this->get(route('advice'))->assertOk()->assertDontSee('data-advice-votes', false);
});

test('database prevents duplicate votes and deleting a user or article cleans up its votes', function () {
    $other = User::factory()->create();
    $data = ['user_id' => $this->user->id, 'conseil_id' => $this->article->id, 'value' => 1];
    ConseilVote::create($data);
    expect(fn () => ConseilVote::create($data))->toThrow(QueryException::class);
    ConseilVote::create(['user_id' => $other->id, 'conseil_id' => $this->article->id, 'value' => -1]);
    $this->user->delete();
    expect(ConseilVote::count())->toBe(1);
    $this->article->delete();
    expect(ConseilVote::count())->toBe(0);
});
