@props(['conseil', 'returnTo' => '/advice', 'onArticle' => false, 'iconOnly' => false])
@auth
    <form method="POST" action="{{ route($conseil->is_saved ? 'advice.bookmark.destroy' : 'advice.bookmark.store', $conseil) }}" class="ha-bookmark-form"
        @if($onArticle || !str_starts_with($returnTo, '/advice/saved')) data-advice-bookmark @endif
        data-save-text="{{ $onArticle ? __('Save article') : __('Save') }}" data-saved-text="{{ __('Saved') }}"
        data-save-label="{{ __('Save :title', ['title' => $conseil->titre]) }}"
        data-remove-label="{{ __('Remove :title from saved advice', ['title' => $conseil->titre]) }}"
        data-sign-in-url="{{ route('advice.save-prompt', ['conseil' => $conseil, 'return' => $returnTo]) }}"
        data-error="{{ __('Could not update saved advice. Please try again.') }}"
        data-session-error="{{ __('Your session expired. Refresh the page and try again.') }}">
        @csrf @method($conseil->is_saved ? 'DELETE' : 'PUT')
        <input type="hidden" name="return" value="{{ $returnTo }}">
        <input type="hidden" name="article" value="{{ $onArticle ? 1 : 0 }}">
        <button class="ha-btn ha-btn--outline ha-btn--sm ha-bookmark-button {{ $iconOnly ? 'ha-bookmark-button--icon' : '' }}" aria-pressed="{{ $conseil->is_saved ? 'true' : 'false' }}" aria-label="{{ $conseil->is_saved ? __('Remove :title from saved advice', ['title' => $conseil->titre]) : __('Save :title', ['title' => $conseil->titre]) }}" @if($iconOnly) title="{{ $conseil->is_saved ? __('Remove :title from saved advice', ['title' => $conseil->titre]) : __('Save :title', ['title' => $conseil->titre]) }}" @endif>
            <span data-bookmark-icon @if($conseil->is_saved) hidden @endif><x-ha.icon name="bookmark" size="sm" /></span>
            <span data-bookmark-check @unless($conseil->is_saved) hidden @endunless><x-ha.icon name="bookmark-check" size="sm" /></span>
            <span data-bookmark-label @if($iconOnly) class="sr-only" @endif>{{ $conseil->is_saved ? __('Saved') : ($onArticle ? __('Save article') : __('Save')) }}</span>
        </button>
        <span class="ha-bookmark-feedback sr-only" role="status" data-bookmark-feedback></span>
    </form>
@else
    <a class="ha-btn ha-btn--ghost ha-btn--sm {{ $iconOnly ? 'ha-bookmark-button--icon' : '' }}" href="{{ route('advice.save-prompt', ['conseil' => $conseil, 'return' => $returnTo]) }}" @if($iconOnly) aria-label="{{ __('Sign in to save') }}: {{ $conseil->titre }}" title="{{ __('Sign in to save') }}" @endif><x-ha.icon name="bookmark" size="sm" /><span @if($iconOnly) class="sr-only" @endif>{{ __('Sign in to save') }}</span></a>
@endauth
