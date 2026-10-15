@props(['conseil', 'returnTo' => '/advice'])
<article class="ha-card ha-advice-card">
    <div class="ha-advice-card-head">
        <h3 class="ha-advice-title"><a href="{{ route('advice.show', ['conseil' => $conseil, 'return' => $returnTo]) }}">{{ $conseil->titre }}</a></h3>
        <x-advice.bookmark :conseil="$conseil" :return-to="$returnTo" :icon-only="true" />
    </div>
    <p class="ha-advice-excerpt">{{ $conseil->resume }}</p>
    <div class="ha-advice-meta ha-advice-card-tags">
        <x-ha.badge variant="cool" :dot="false" :title="$conseil->situationLabel()">{{ $conseil->situation === 'both' ? __('Heat & outages') : $conseil->situationLabel() }}</x-ha.badge>
        <x-ha.badge variant="neutral" :dot="false" :title="$conseil->audienceLabel()">{{ $conseil->public_cible === 'parents' ? __('Families') : $conseil->audienceLabel() }}</x-ha.badge>
    </div>
    <div class="ha-advice-foot"><span class="ha-advice-meta"><x-ha.icon name="clock" size="sm" />{{ __(':minutes min read', ['minutes' => $conseil->readingMinutes()]) }}</span><a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('advice.show', ['conseil' => $conseil, 'return' => $returnTo]) }}" aria-label="{{ __('Read :title', ['title' => $conseil->titre]) }}">{{ __('Read advice') }}<x-ha.icon name="arrow-right" size="sm" class="rtl:rotate-180" /></a></div>
</article>
