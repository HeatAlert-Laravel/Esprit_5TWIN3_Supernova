{{-- Module 5 household risk advisor. The score is computed locally by Laravel; Mistral only explains it. Nothing is stored. --}}
<section class="ha-card" aria-labelledby="household-risk-title"
    data-household-risk
    data-url="{{ route('profile.ai-risk.analyze') }}">
    <div class="ha-card__head">
        <h2 class="ha-card__title ha-card__title--with-icon" id="household-risk-title">
            <span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="lightbulb" /></span>
            Analyse intelligente du risque du foyer
        </h2>
    </div>
    <p class="ha-muted">Un score local reproductible, complété par une explication et des actions prioritaires pour votre foyer.</p>

    @if ($profile)
        <ul class="ha-list mt-4">
            <li><span>Équipements sensibles</span><strong>{{ $profile->sensitiveEquipments->count() }}</strong></li>
            <li><span>Personne fragile dans le foyer</span><strong>{{ $profile->has_fragile_person ? 'Oui' : 'Non' }}</strong></li>
        </ul>

        <button type="button" class="ha-btn ha-btn--primary ha-btn--block mt-4" data-household-risk-run>
            <x-ha.icon name="lightbulb" size="sm" /><span data-household-risk-label>Analyser mon foyer</span>
        </button>

        <p class="ha-error mt-3" data-household-risk-error role="alert" hidden></p>

        <div class="mt-4" data-household-risk-result aria-live="polite" aria-busy="false" hidden>
            <div class="ha-row-between">
                <span class="ha-eyebrow">Niveau</span>
                <span class="ha-badge" data-household-risk-level>—</span>
            </div>
            <p class="ha-kpi__value mt-2"><span data-household-risk-score>—</span> <small class="ha-muted">/ 100</small></p>
            <div class="ha-progress mb-4" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="Score d’exposition du foyer">
                <div class="ha-progress__bar" data-household-risk-bar style="width: 0%"></div>
            </div>

            <h3>Pourquoi</h3>
            <ul class="ha-list" data-household-risk-factors></ul>

            <h3 class="mt-4">Analyse IA</h3>
            <p class="ha-alert ha-alert--warning" data-household-risk-fallback hidden></p>
            <p data-household-risk-summary></p>
            <ul class="ha-list" data-household-risk-ai-factors></ul>

            <h3 class="mt-4">Actions prioritaires</h3>
            <ol class="ha-list" data-household-risk-actions></ol>
        </div>

        <p class="ha-help mt-4">Les recommandations IA sont fournies à titre préventif et ne remplacent pas les consignes des autorités ou des professionnels compétents.</p>
    @else
        <div class="ha-alert ha-alert--info mt-4"><x-ha.icon name="info" /><div>Complétez d’abord votre profil de foyer avant de lancer l’analyse.</div></div>
    @endif
</section>
