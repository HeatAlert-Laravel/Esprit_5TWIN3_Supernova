// Module 5 household risk advisor: calls the dedicated endpoint and renders the result with
// textContent only (AI output is untrusted and is never injected as HTML).

const LEVEL_VARIANTS = { HIGH: 'ha-badge--danger', MEDIUM: 'ha-badge--warning', LOW: 'ha-badge--success' };

const GENERIC_ERROR = 'L’analyse n’a pas pu être lancée. Réessayez dans un instant.';
const RATE_LIMIT_ERROR = 'Trop d’analyses en peu de temps. Réessayez dans une minute.';

export function initHouseholdRisk() {
    document.querySelectorAll('[data-household-risk]').forEach((root) => initPanel(root));
}

function initPanel(root) {
    const button = root.querySelector('[data-household-risk-run]');
    const url = root.dataset.url;

    if (!button || !url) {
        return;
    }

    const label = root.querySelector('[data-household-risk-label]');
    const errorBox = root.querySelector('[data-household-risk-error]');
    const result = root.querySelector('[data-household-risk-result]');
    const levelBadge = root.querySelector('[data-household-risk-level]');
    const score = root.querySelector('[data-household-risk-score]');
    const bar = root.querySelector('[data-household-risk-bar]');
    const factors = root.querySelector('[data-household-risk-factors]');
    const fallback = root.querySelector('[data-household-risk-fallback]');
    const summary = root.querySelector('[data-household-risk-summary]');
    const aiFactors = root.querySelector('[data-household-risk-ai-factors]');
    const actions = root.querySelector('[data-household-risk-actions]');
    const defaultLabel = label ? label.textContent : '';

    const setBusy = (busy) => {
        button.disabled = busy;
        button.setAttribute('aria-busy', String(busy));
        if (label) label.textContent = busy ? 'Analyse en cours…' : defaultLabel;
        if (result) result.setAttribute('aria-busy', String(busy));
    };

    const showError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.hidden = false;
    };

    const clearList = (element) => {
        if (element) element.replaceChildren();
    };

    const addItems = (element, values) => {
        if (!element) return;
        values.forEach((value) => {
            const item = document.createElement('li');
            item.textContent = value;
            element.append(item);
        });
    };

    const render = (payload) => {
        if (errorBox) errorBox.hidden = true;
        if (!result) return;

        const value = Math.max(0, Math.min(100, Number(payload.score) || 0));
        if (score) score.textContent = String(value);
        if (bar) bar.style.width = `${value}%`;

        if (levelBadge) {
            const level = typeof payload.level === 'string' ? payload.level : '—';
            levelBadge.className = `ha-badge ${LEVEL_VARIANTS[level] || 'ha-badge--neutral'}`;
            levelBadge.textContent = level;
        }

        clearList(factors);
        addItems(factors, (payload.factors || []).map((factor) => factor.label));

        clearList(aiFactors);
        clearList(actions);

        const ai = payload.ai && typeof payload.ai === 'object' ? payload.ai : null;

        if (ai) {
            if (fallback) fallback.hidden = true;
            if (summary) summary.textContent = ai.summary || '';
            addItems(aiFactors, Array.isArray(ai.risk_factors) ? ai.risk_factors : []);
            addItems(actions, (Array.isArray(ai.priority_actions) ? ai.priority_actions : [])
                .map((entry) => `${entry.priority}. ${entry.action}`));
        } else {
            if (summary) summary.textContent = '';
            if (fallback) {
                fallback.textContent = payload.message || 'L’analyse IA est temporairement indisponible.';
                fallback.hidden = false;
            }
            addItems(actions, Array.isArray(payload.local_actions) ? payload.local_actions : []);
        }

        result.hidden = false;
    };

    button.addEventListener('click', async () => {
        setBusy(true);
        if (errorBox) errorBox.hidden = true;

        try {
            const token = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ? token.content : '',
                },
                body: '{}',
            });

            let payload = {};
            try {
                payload = await response.json();
            } catch {
                payload = {};
            }

            if (response.status === 429) {
                showError(RATE_LIMIT_ERROR);
                return;
            }

            if (!response.ok || payload.ok !== true) {
                showError(typeof payload.message === 'string' ? payload.message : GENERIC_ERROR);
                return;
            }

            render(payload);
        } catch {
            showError(GENERIC_ERROR);
        } finally {
            setBusy(false);
        }
    });
}
