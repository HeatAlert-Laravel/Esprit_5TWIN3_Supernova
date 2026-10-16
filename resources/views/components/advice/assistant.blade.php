@props(['conseil'])
<section id="article-assistant" class="ha-article-assistant" hidden data-assistant-panel aria-labelledby="assistant-heading"
    data-loading="{{ __('Thinking…') }}" data-error="{{ __('The article assistant is temporarily unavailable. Please try again later.') }}"
    data-busy="{{ __('The AI service is temporarily busy. Please try again in a moment.') }}"
    data-limit="{{ __('The assistant is busy or its request limit has been reached. Please try again later.') }}"
    data-session="{{ __('Your session expired. Refresh the page and try again.') }}"
    data-question-error="{{ __('Enter a question about this article (up to 500 characters).') }}"
    data-summary="{{ __('Key points') }}" data-checklist="{{ __('Your checklist') }}" data-ask="{{ __('Your answer') }}"
    data-ai-name="{{ __('HeatAlert AI') }}" data-user-name="{{ auth()->user()?->name ?? __('You') }}"
    data-user-initial="{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? __('You'), 0, 1)) }}"
    data-summary-prompt="{{ __('Summarize this article.') }}" data-checklist-prompt="{{ __('Make a checklist from this article.') }}" data-retry="{{ __('Try again') }}">
    <div class="ha-assistant-head"><span class="ha-chat-avatar ha-chat-avatar--ai" aria-hidden="true">✦</span><div><h2 id="assistant-heading">{{ __('HeatAlert AI') }}</h2><p class="ha-help ha-assistant-context" title="{{ $conseil->titre }}">{{ $conseil->titre }}</p></div><button type="button" class="ha-btn ha-btn--ghost ha-reader-icon" data-assistant-close aria-label="{{ __('Close assistant') }}">×</button></div>
    <form method="POST" action="{{ route('advice.assistant', $conseil) }}" novalidate data-assistant-form>
        @csrf
        <div class="ha-assistant-shortcuts"><button type="submit" name="action" value="summary" class="ha-btn ha-btn--outline ha-btn--sm">{{ __('Summarize') }}</button><button type="submit" name="action" value="checklist" class="ha-btn ha-btn--outline ha-btn--sm">{{ __('Make a checklist') }}</button></div>
        <div class="ha-assistant-conversation" data-assistant-conversation>
            <div class="ha-assistant-welcome" data-assistant-welcome><span aria-hidden="true">✦</span><h3>{{ __('Make this article easier to use') }}</h3><p>{{ __('Get the key points, build a checklist, or ask a question about what you are reading.') }}</p></div>
            <div class="ha-chat-thread" role="log" aria-label="{{ __('Article conversation') }}" aria-live="polite" aria-relevant="additions text" data-assistant-messages></div>
            <p class="sr-only" role="status" data-assistant-status></p>
        </div>
        <div class="ha-assistant-composer">
            <label for="assistant-question" class="ha-label">{{ __('Ask about this article') }}</label>
            <div class="ha-assistant-question"><textarea id="assistant-question" name="question" rows="1" maxlength="500" class="ha-input" placeholder="{{ __('What would you like to know?') }}" aria-describedby="assistant-question-error" data-assistant-question></textarea><button type="submit" name="action" value="ask" class="ha-btn ha-btn--primary" aria-label="{{ __('Send question') }}" title="{{ __('Send question') }}"><x-ha.icon name="arrow-up" size="sm" /></button></div>
            <p id="assistant-question-error" class="ha-assistant-error" hidden data-assistant-question-error></p>
            <p class="ha-help ha-assistant-note">{{ __('AI-generated · Check the original advice.') }}</p>
        </div>
    </form>
</section>
