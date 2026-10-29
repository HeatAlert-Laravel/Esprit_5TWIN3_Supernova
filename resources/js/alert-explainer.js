// "Explain this alert": asks the server for a plain-language explanation in the chosen language.
// The server calls Google Gemini and caches the answer; the browser never sees the API key.
const RTL = ['ar', 'tn'];

function render(result, data) {
    result.replaceChildren();
    result.hidden = false;
    result.dir = RTL.includes(data.lang) ? 'rtl' : 'ltr';
    result.lang = RTL.includes(data.lang) ? 'ar' : data.lang;

    const summary = document.createElement('p');
    summary.className = 'wx-explain__summary';
    summary.textContent = data.summary;

    const list = document.createElement('ul');
    list.className = 'wx-explain__actions';
    data.actions.forEach((action) => {
        const item = document.createElement('li');
        item.textContent = action;
        list.append(item);
    });

    result.append(summary, list);
}

function showError(result, message) {
    result.replaceChildren();
    result.hidden = false;
    result.dir = 'ltr';
    result.lang = 'en';
    const error = document.createElement('p');
    error.className = 'wx-explain__error';
    error.textContent = message;
    result.append(error);
}

export function initAlertExplainer() {
    document.querySelectorAll('[data-alert-explainer]').forEach((panel) => {
        const run = panel.querySelector('[data-explain-run]');
        const result = panel.querySelector('[data-explain-result]');
        const buttons = Array.from(panel.querySelectorAll('[data-lang]'));
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        let lang = buttons.find((button) => button.getAttribute('aria-checked') === 'true')?.dataset.lang || 'en';
        let busy = false;

        const select = (code) => {
            lang = code;
            buttons.forEach((button) => button.setAttribute('aria-checked', button.dataset.lang === code ? 'true' : 'false'));
        };

        const explain = async () => {
            if (busy) return;
            busy = true;
            run.disabled = true;
            run.textContent = 'Writing…';
            result.hidden = false;
            result.dir = 'ltr';
            result.textContent = 'Writing the explanation… this can take up to a minute when the service is busy.';

            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token || '', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ lang }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showError(result, data.message || 'The explanation could not be produced. Please try again.');
                } else {
                    render(result, data);
                }
            } catch {
                showError(result, 'The explanation could not be loaded. Check your connection and try again.');
            } finally {
                busy = false;
                run.disabled = false;
                run.textContent = 'Explain again';
            }
        };

        buttons.forEach((button) => button.addEventListener('click', () => {
            select(button.dataset.lang);
            // Once an explanation is on screen, switching language rewrites it right away.
            if (!result.hidden && !result.querySelector('.wx-explain__error')) explain();
        }));
        run.addEventListener('click', explain);
    });
}
