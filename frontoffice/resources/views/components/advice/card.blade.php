@props(['conseil'])
<article class="ha-card ha-advice-card">
    <div class="ha-advice-meta"><x-ha.badge variant="cool"><x-ha.icon :name="$conseil->situationIcon()" size="sm" />{{ $conseil->situationLabel() }}</x-ha.badge><x-ha.badge variant="neutral">{{ $conseil->audienceLabel() }}</x-ha.badge></div>
    <h3 class="ha-advice-title"><a href="{{ route('advice.show', $conseil) }}">{{ $conseil->titre }}</a></h3>
    <p class="ha-advice-excerpt">{{ $conseil->resume }}</p>
    <div class="ha-advice-foot"><span class="ha-advice-meta"><x-ha.icon name="clock" size="sm" />{{ __(':minutes min read', ['minutes' => $conseil->readingMinutes()]) }}</span><a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('advice.show', $conseil) }}" aria-label="{{ __('Read :title', ['title' => $conseil->titre]) }}">{{ __('Read advice') }}<x-ha.icon name="arrow-right" size="sm" class="rtl:rotate-180" /></a></div>
</article>
