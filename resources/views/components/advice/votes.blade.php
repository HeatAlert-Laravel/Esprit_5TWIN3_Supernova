@props(['conseil', 'currentVote' => 0, 'returnTo' => '/advice'])
<div class="ha-advice-feedback" id="article-feedback" data-advice-votes
    data-error="{{ __('Your vote could not be saved. Please try again.') }}"
    data-session-error="{{ __('Your session expired. Refresh the page and try again.') }}"
    data-limit-error="{{ __('Please wait a moment before voting again.') }}"
    data-sign-in-url="{{ route('advice.vote.prompt', ['conseil' => $conseil, 'return' => $returnTo]) }}">
    <span class="sr-only">{{ __('Was this advice useful?') }}</span>
    @auth
        <form method="POST" action="{{ route('advice.vote.update', $conseil) }}" class="ha-vote-pill" data-vote-form>
            @csrf @method('PUT')
            <input type="hidden" name="return" value="{{ $returnTo }}">
            <button type="submit" name="value" value="{{ $currentVote === 1 ? 0 : 1 }}" class="ha-vote-button ha-vote-button--up"
                data-vote-direction="1" aria-pressed="{{ $currentVote === 1 ? 'true' : 'false' }}" aria-label="{{ __('Upvote this advice') }}" title="{{ __('Upvote · Click again to remove') }}"><x-ha.icon name="arrow-up" size="sm" /></button>
            <output class="ha-vote-score" data-vote-score data-vote="{{ $currentVote }}" aria-label="{{ __('Usefulness score') }}">{{ $conseil->voteScore() }}</output>
            <button type="submit" name="value" value="{{ $currentVote === -1 ? 0 : -1 }}" class="ha-vote-button ha-vote-button--down"
                data-vote-direction="-1" aria-pressed="{{ $currentVote === -1 ? 'true' : 'false' }}" aria-label="{{ __('Downvote this advice') }}" title="{{ __('Downvote · Click again to remove') }}"><x-ha.icon name="arrow-down" size="sm" /></button>
        </form>
    @else
        <div class="ha-vote-pill">
            <a href="{{ route('advice.vote.prompt', ['conseil' => $conseil, 'return' => $returnTo]) }}" class="ha-vote-button ha-vote-button--up" aria-label="{{ __('Sign in to upvote') }}" title="{{ __('Sign in to upvote') }}"><x-ha.icon name="arrow-up" size="sm" /></a>
            <output class="ha-vote-score" aria-label="{{ __('Usefulness score') }}">{{ $conseil->voteScore() }}</output>
            <a href="{{ route('advice.vote.prompt', ['conseil' => $conseil, 'return' => $returnTo]) }}" class="ha-vote-button ha-vote-button--down" aria-label="{{ __('Sign in to downvote') }}" title="{{ __('Sign in to downvote') }}"><x-ha.icon name="arrow-down" size="sm" /></a>
        </div>
    @endauth
    <span class="ha-vote-feedback sr-only" role="status" data-vote-feedback></span>
</div>
