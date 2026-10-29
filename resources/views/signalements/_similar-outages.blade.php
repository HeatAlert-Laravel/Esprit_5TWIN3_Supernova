<section class="ha-similar" data-similar-outages data-url="{{ route('outages.report.similar') }}" aria-labelledby="similar-outages-title">
    <div class="ha-similar__header"><span class="ha-similar__icon"><x-ha.icon name="search" /></span><div><span class="ha-similar__eyebrow">AIDE AU SIGNALEMENT · FACULTATIF</span><h2 id="similar-outages-title">Une coupure existe peut-être déjà</h2></div></div>
    <p class="ha-similar__intro">Décrivez votre problème ci-dessus et choisissez votre quartier. Vous gardez le choix de l’association et de l’envoi.</p>
    <div class="ha-similar__controls">
        <div class="ha-field"><label class="ha-label" for="similar-quartier">Quartier pour la recherche</label><select id="similar-quartier" name="search_quartier_id" class="ha-select" data-similar-quartier><option value="">Choisir mon quartier</option>@foreach($quartiers as $quartier)<option value="{{ $quartier->id }}" @selected((string) old('search_quartier_id') === (string) $quartier->id)>{{ $quartier->nom }} — {{ $quartier->ville }}</option>@endforeach</select></div>
        <button type="button" class="ha-btn ha-btn--outline" data-similar-search><x-ha.icon name="search" size="sm" />Rechercher avec l'IA une coupure similaire</button>
    </div>
    <p class="ha-similar__privacy">Seules les descriptions nettoyées et la localisation générale sont analysées par Gemini, ou OpenRouter en secours. La recherche ne transmet pas votre champ d’adresse personnelle.</p>
    <noscript><p>La recherche IA nécessite JavaScript. Vous pouvez sélectionner une coupure et envoyer votre signalement normalement.</p></noscript>
    <div class="ha-similar__loading" data-similar-loading role="status" hidden><span class="ha-similar__spinner" aria-hidden="true"></span>Recherche des coupures pouvant correspondre à votre situation…</div>
    <p class="ha-similar__error" data-similar-error role="alert" hidden></p>
    <p class="ha-similar__selection" data-similar-selection role="status" hidden></p>
    <div class="ha-similar__results" data-similar-results aria-live="polite" hidden>
        <div class="ha-similar__results-head"><h3>Résultats de la recherche</h3><button type="button" class="ha-btn ha-btn--ghost ha-btn--sm" data-similar-ignore>Ignorer les suggestions</button></div>
        <p class="ha-similar__message" data-similar-message></p>
        <div class="ha-similar__cards" data-similar-cards></div>
    </div>
</section>
