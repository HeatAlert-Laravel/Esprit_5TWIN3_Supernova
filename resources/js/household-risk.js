// Module 5 household risk advisor: calls the dedicated endpoint and renders the result with
// textContent only (AI output is untrusted and is never injected as HTML).

const LEVEL_VARIANTS = { HIGH: 'ha-badge--danger', MEDIUM: 'ha-badge--warning', LOW: 'ha-badge--success' };
const FACTOR_LEVEL_VARIANTS = { CRITICAL: 'ha-badge--critical', HIGH: 'ha-badge--danger', MEDIUM: 'ha-badge--warning', LOW: 'ha-badge--success' };

const FACTOR_ICONS = {
    fragile_person: 'M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27',
    equipment: 'M12 22v-5M9 8V2m6 6V2m3 6v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z',
    info: 'M12 16v-4m0-4h.01 M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10Z',
};

const GENERIC_ERROR = 'L’analyse n’a pas pu être lancée. Réessayez dans un instant.';
const RATE_LIMIT_ERROR = 'Trop d’analyses en peu de temps. Réessayez dans une minute.';
const FALLBACK_MESSAGE = 'L’analyse IA est temporairement indisponible. Votre évaluation locale HeatAlert reste disponible.';

const MAX_ACTIONS = 5;

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
    const icon = root.querySelector('[data-household-risk-icon]');
    const spinner = root.querySelector('[data-household-risk-spinner]');
    const errorBox = root.querySelector('[data-household-risk-error]');
    const scoreBlock = root.querySelector('[data-household-risk-score-block]');
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
        if (spinner) spinner.hidden = !busy;
        if (icon) icon.hidden = busy;
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

    const makeIcon = (pathData) => {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        svg.classList.add('ha-icon');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', pathData);
        svg.append(path);
        return svg;
    };

    const makeBadge = (text, variant) => {
        const badge = document.createElement('span');
        badge.className = `ha-badge ${variant}`;
        badge.textContent = text;
        return badge;
    };

    const renderFactors = (items) => {
        clearList(factors);
        (items || []).forEach((factor) => {
            const item = document.createElement('li');
            item.className = 'ha-risk-factor';

            const chip = document.createElement('span');
            chip.className = 'ha-icon-chip';
            chip.append(makeIcon(FACTOR_ICONS[factor.kind] || FACTOR_ICONS.info));
            item.append(chip);

            const body = document.createElement('div');
            body.className = 'ha-risk-factor__body';

            const head = document.createElement('p');
            head.className = 'ha-risk-factor__head';
            const title = document.createElement('strong');
            title.textContent = factor.title || factor.label || '';
            head.append(title);
            if (typeof factor.level === 'string' && factor.level !== '') {
                const level = factor.level.toUpperCase();
                head.append(makeBadge(level, FACTOR_LEVEL_VARIANTS[level] || 'ha-badge--neutral'));
            }
            body.append(head);

            const tags = Array.isArray(factor.tags) ? factor.tags : [];
            if (tags.length > 0) {
                const pills = document.createElement('p');
                pills.className = 'ha-risk-factor__detail';
                tags.forEach((tag) => {
                    const pill = document.createElement('span');
                    pill.className = 'ha-risk-factor__tag';
                    pill.textContent = tag;
                    pills.append(pill);
                });
                body.append(pills);
            } else if (typeof factor.detail === 'string' && factor.detail !== '') {
                const detail = document.createElement('p');
                detail.className = 'ha-risk-factor__detail';
                detail.textContent = factor.detail;
                body.append(detail);
            }

            item.append(body);
            factors.append(item);
        });
    };

    const splitSentence = (text) => {
        const match = text.match(/^(.*?[.!?])\s+(.+)$/);
        return match ? { title: match[1], description: match[2] } : { title: text, description: '' };
    };

    const renderActions = (list, items) => {
        clearList(list);
        (items || []).slice(0, MAX_ACTIONS).forEach((entry, index) => {
            const text = typeof entry === 'string' ? entry : String(entry?.action || '');
            if (text === '') {
                return;
            }
            const { title, description } = splitSentence(text);

            const item = document.createElement('li');
            item.className = 'ha-action';

            const number = document.createElement('span');
            number.className = 'ha-action__num';
            number.textContent = String(index + 1);
            item.append(number);

            const body = document.createElement('div');
            body.className = 'ha-action__body';
            const heading = document.createElement('p');
            heading.className = 'ha-action__title';
            heading.textContent = title;
            body.append(heading);
            if (description !== '') {
                const detail = document.createElement('p');
                detail.className = 'ha-action__desc';
                detail.textContent = description;
                body.append(detail);
            }
            item.append(body);
            list.append(item);
        });
    };

    const render = (payload) => {
        if (errorBox) errorBox.hidden = true;
        if (!result) return;

        const value = Math.max(0, Math.min(100, Number(payload.score) || 0));
        if (score) score.textContent = String(value);
        if (bar) {
            bar.style.width = `${value}%`;
            bar.parentElement.setAttribute('aria-valuenow', String(value));
        }
        if (scoreBlock) scoreBlock.hidden = false;

        if (levelBadge) {
            const level = typeof payload.level === 'string' ? payload.level : '—';
            levelBadge.className = `ha-badge ${LEVEL_VARIANTS[level] || 'ha-badge--neutral'}`;
            levelBadge.textContent = level;
            levelBadge.hidden = false;
        }

        renderFactors(payload.factors);
        clearList(aiFactors);
        clearList(actions);

        const ai = payload.ai && typeof payload.ai === 'object' ? payload.ai : null;

        if (ai) {
            if (fallback) fallback.hidden = true;
            if (summary) summary.textContent = typeof ai.summary === 'string' ? ai.summary : '';
            (Array.isArray(ai.risk_factors) ? ai.risk_factors : []).forEach((factor) => {
                const item = document.createElement('li');
                item.textContent = factor;
                aiFactors.append(item);
            });
            renderActions(actions, Array.isArray(ai.priority_actions) ? ai.priority_actions : []);
        } else {
            if (summary) summary.textContent = '';
            if (fallback) {
                fallback.textContent = typeof payload.message === 'string' && payload.message !== '' ? payload.message : FALLBACK_MESSAGE;
                fallback.hidden = false;
            }
            renderActions(actions, Array.isArray(payload.local_actions) ? payload.local_actions : []);
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
