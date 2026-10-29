<section class="ha-similar" data-similar-outages data-url="{{ route('outages.report.similar') }}" aria-labelledby="similar-outages-title">
    <div class="ha-similar__header"><span class="ha-similar__icon"><x-ha.icon name="search" /></span><div><span class="ha-similar__eyebrow">REPORTING ASSISTANT · OPTIONAL</span><h2 id="similar-outages-title">An existing outage may match your issue</h2></div></div>
    <p class="ha-similar__intro">Describe your issue above and choose your neighborhood. You decide which outage to select and when to submit.</p>
    <div class="ha-similar__controls">
        <div class="ha-field"><label class="ha-label" for="similar-quartier">Search neighborhood</label><select id="similar-quartier" name="search_quartier_id" class="ha-select" data-similar-quartier><option value="">Choose my neighborhood</option>@foreach($quartiers as $quartier)<option value="{{ $quartier->id }}" @selected((string) old('search_quartier_id') === (string) $quartier->id)>{{ $quartier->nom }} — {{ $quartier->ville }}</option>@endforeach</select></div>
        <button type="button" class="ha-btn ha-btn--outline" data-similar-search><x-ha.icon name="search" size="sm" />Find a similar outage with AI</button>
    </div>
    <p class="ha-similar__privacy">Gemini, or OpenRouter as a backup, analyzes cleaned descriptions and your general location. Your personal address field is not sent to these providers.</p>
    <noscript><p>AI search requires JavaScript. You can still select an outage and submit your report normally.</p></noscript>
    <div class="ha-similar__loading" data-similar-loading role="status" hidden><span class="ha-similar__spinner" aria-hidden="true"></span>Searching for outages that may match your situation…</div>
    <p class="ha-similar__error" data-similar-error role="alert" hidden></p>
    <p class="ha-similar__selection" data-similar-selection role="status" hidden></p>
    <div class="ha-similar__results" data-similar-results aria-live="polite" hidden>
        <div class="ha-similar__results-head"><h3>Search results</h3><button type="button" class="ha-btn ha-btn--ghost ha-btn--sm" data-similar-ignore>Dismiss suggestions</button></div>
        <p class="ha-similar__message" data-similar-message></p>
        <div class="ha-similar__cards" data-similar-cards></div>
    </div>
</section>
