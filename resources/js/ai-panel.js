// AI panels ("Explain this alert", "Your week at a glance"): ask the server for a short text in the chosen language.
// The server calls Google Gemini and caches the answer; the browser never sees the API key.
// The answer is either { summary, actions } or { headline, points }.
const RTL = ['ar', 'tn'];

function setDirection(result, lang) {
    result.dir = RTL.includes(lang) ? 'rtl' : 'ltr';
    result.lang = RTL.includes(lang) ? 'ar' : lang;
}

function render(result, data) {
    result.replaceChildren();
    result.hidden = false;
    result.classList.remove('is-error');
    setDirection(result, data.lang);

    const lead = document.createElement('p');
    lead.className = 'ai-panel__lead';
    lead.textContent = data.headline ?? data.summary;

    const list = document.createElement('ul');
    list.className = 'ai-panel__list';
    (data.points ?? data.actions ?? []).forEach((text) => {
        const item = document.createElement('li');
        item.textContent = text;
        list.append(item);
    });

    result.append(lead, list);
}

function showLoading(result) {
    result.replaceChildren();
    result.hidden = false;
    result.classList.remove('is-error');
    result.dir = 'ltr';
    result.lang = 'en';

    const skeleton = document.createElement('div');
    skeleton.className = 'ai-skeleton';
    skeleton.setAttribute('aria-hidden', 'true');
    ['wide', '', 'short'].forEach((size) => {
        const line = document.createElement('span');
        line.className = `ai-skeleton__line ${size ? `ai-skeleton__line--${size}` : ''}`.trim();
        skeleton.append(line);
    });

    const hint = document.createElement('p');
    hint.className = 'ai-panel__hint';
    hint.textContent = 'Writing… this can take up to a minute when the AI service is busy.';

    result.append(skeleton, hint);
}

function showError(result, message) {
    result.replaceChildren();
    result.hidden = false;
    result.classList.add('is-error');
    result.dir = 'ltr';
    result.lang = 'en';

    const error = document.createElement('p');
    error.className = 'ai-panel__error';
    error.textContent = message;
    result.append(error);
}

export function initAiPanels() {
    document.querySelectorAll('[data-ai-panel]').forEach((panel) => {
        const run = panel.querySelector('[data-ai-run]');
        const label = run.querySelector('span');
        const result = panel.querySelector('[data-ai-result]');
        const buttons = Array.from(panel.querySelectorAll('[data-lang]'));
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        let lang = buttons.find((button) => button.getAttribute('aria-checked') === 'true')?.dataset.lang || 'en';
        let busy = false;
        let hasResult = false;

        const select = (code) => {
            lang = code;
            buttons.forEach((button) => button.setAttribute('aria-checked', button.dataset.lang === code ? 'true' : 'false'));
        };

        const generate = async () => {
            if (busy) return;
            busy = true;
            hasResult = false;
            run.disabled = true;
            label.textContent = 'Writing…';
            result.setAttribute('aria-busy', 'true');
            showLoading(result);

            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token || '', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ lang }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showError(result, data.message || 'The text could not be produced. Please try again.');
                } else {
                    render(result, data);
                    hasResult = true;
                }
            } catch {
                showError(result, 'The text could not be loaded. Check your connection and try again.');
            } finally {
                busy = false;
                run.disabled = false;
                label.textContent = hasResult ? 'Regenerate' : run.dataset.label;
                result.setAttribute('aria-busy', 'false');
            }
        };

        buttons.forEach((button) => button.addEventListener('click', () => {
            select(button.dataset.lang);
            // Once a text is on screen, switching language rewrites it right away.
            if (hasResult) generate();
        }));
        run.addEventListener('click', generate);
    });
}
