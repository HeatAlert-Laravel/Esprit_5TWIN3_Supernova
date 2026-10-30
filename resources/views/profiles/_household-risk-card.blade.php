{{-- Module 5 household risk advisor. The score is computed locally by Laravel; Mistral only explains it. Nothing is stored. --}}
<section class="ha-card" aria-labelledby="household-risk-title"
    data-household-risk
    data-url="{{ route('profile.ai-risk.analyze') }}">
    <div class="ha-card__head">
        <h2 class="ha-card__title ha-card__title--with-icon" id="household-risk-title">
            <span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="lightbulb" /></span>
            Analyse intelligente du risque du foyer
        </h2>
        <span class="ha-badge ha-badge--neutral" data-household-risk-level hidden>—</span>
    </div>
    <p class="ha-muted">Un score local reproductible, complété par une explication et des actions prioritaires pour votre foyer.</p>

    @if ($profile)
        <ul class="ha-hints mt-4">
            <li><x-ha.icon name="plug" size="sm" /><span><strong>{{ $profile->sensitiveEquipments->count() }}</strong> équipement(s) sensible(s)</span></li>
            <li><x-ha.icon name="heart-pulse" size="sm" /><span>Personne fragile : <strong>{{ $profile->has_fragile_person ? 'Oui' : 'Non' }}</strong></span></li>
        </ul>

        <div class="ha-risk-score mt-4" data-household-risk-score-block hidden>
            <p class="ha-risk-score__value"><span data-household-risk-score>—</span><small>/ 100</small></p>
            <div class="ha-progress ha-progress--ember" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Score HeatAlert d’exposition et de préparation du foyer">
                <div class="ha-progress__bar" data-household-risk-bar style="width: 0%"></div>
            </div>
            <p class="ha-risk-score__caption">Score HeatAlert d’exposition et de préparation du foyer — indicateur interne d’organisation, sans valeur médicale ni prédictive.</p>
        </div>

        <button type="button" class="ha-btn ha-btn--primary ha-btn--block mt-4" data-household-risk-run>
            <span class="ha-spinner" data-household-risk-spinner hidden aria-hidden="true"></span>
            <x-ha.icon name="lightbulb" size="sm" data-household-risk-icon />
            <span data-household-risk-label>Analyser mon foyer</span>
        </button>

        <p class="ha-error mt-3" data-household-risk-error role="alert" hidden></p>

        <div class="mt-4" data-household-risk-result aria-live="polite" aria-busy="false" hidden>
            <h3 class="ha-risk-section">Facteurs de risque</h3>
            <ul class="ha-risk-list ha-risk-list--2" data-household-risk-factors></ul>

            <div class="ha-ai-box" data-household-risk-ai-box>
                <div class="ha-ai-box__head">
                    <h3 class="ha-ai-box__title">Analyse IA</h3>
                    <span class="ha-ai-box__provider"><x-ha.icon name="lightbulb" size="sm" />Powered by Mistral AI</span>
                </div>
                <p class="ha-alert ha-alert--warning" data-household-risk-fallback hidden></p>
                <p class="ha-ai-box__summary" data-household-risk-summary></p>
                <ul class="ha-ai-box__factors" data-household-risk-ai-factors></ul>
            </div>

            <h3 class="ha-risk-section">Actions prioritaires</h3>
            <ol class="ha-risk-actions" data-household-risk-actions></ol>
        </div>

        <p class="ha-help mt-4">Les recommandations IA sont fournies à titre préventif et ne remplacent pas les consignes des autorités ou des professionnels compétents.</p>
    @else
        <div class="ha-alert ha-alert--info mt-4"><x-ha.icon name="info" /><div>Complétez d’abord votre profil de foyer avant de lancer l’analyse.</div></div>
    @endif
</section>
