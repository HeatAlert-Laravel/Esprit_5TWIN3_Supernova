export function initOutageReportSummary() {
    document.querySelectorAll('[data-outage-summary]').forEach((panel) => {
        const button = panel.querySelector('[data-summary-button]');
        if (!button) return;
        const loading = panel.querySelector('[data-summary-loading]');
        const error = panel.querySelector('[data-summary-error]');
        const result = panel.querySelector('[data-summary-result]');
        const buttonLabel = button.querySelector('[data-summary-button-label]');
        const renderList = (selector, items, emptyMessage) => {
            const list = panel.querySelector(selector);
            list.replaceChildren();
            if (!items.length) {
                const item = document.createElement('li');
                item.className = 'ha-outage-ai__empty';
                item.textContent = emptyMessage;
                list.append(item);
            }
            items.forEach(({ text, count }) => {
                const item = document.createElement('li');
                item.className = 'ha-outage-ai__item';
                item.textContent = text;
                const source = document.createElement('span');
                source.className = 'ha-outage-ai__source';
                source.textContent = `${count} cited report${count > 1 ? 's' : ''}`;
                item.append(source);
                list.append(item);
            });
        };
        button.addEventListener('click', async () => {
            if (button.disabled) return;
            button.disabled = true;
            panel.setAttribute('aria-busy', 'true');
            loading.hidden = false;
            error.hidden = true;
            result.hidden = true;
            const abort = new AbortController();
            const timer = setTimeout(() => abort.abort(), 55000);
            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'same-origin', signal: abort.signal,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const messages = {
                        401: 'Your session has expired. Please sign in again.',
                        403: 'This action is restricted to authorized administrators.',
                        419: 'Your session has expired. Refresh the page and try again.',
                        429: 'Too many requests. Wait a minute before trying again.',
                        503: 'The AI summary is temporarily unavailable. Please try again later.',
                    };
                    throw new Error(messages[response.status] || (response.status === 409 ? data.message : 'The summary could not be generated. Please try again.'));
                }
                if (data.empty) {
                    error.textContent = data.message;
                    error.hidden = false;
                    return;
                }
                if (!data.summary || !Array.isArray(data.problems) || !Array.isArray(data.attention)) {
                    throw new Error('The response is incomplete. Please try again.');
                }
                panel.querySelector('[data-summary-count]').textContent = `${data.total} report${data.total > 1 ? 's' : ''}`;
                panel.querySelector('[data-summary-text]').textContent = data.summary;
                panel.querySelector('[data-summary-location]').textContent = data.location;
                panel.querySelector('[data-summary-provider]').textContent = `Generated with ${data.provider}`;
                renderList('[data-summary-problems]', data.problems, 'No specific issues could be identified from the descriptions.');
                renderList('[data-summary-attention]', data.attention, 'No particular points were identified.');
                panel.querySelector('[data-summary-note]').textContent =
                    `${data.analyzed} description(s) analyzed with ${data.provider}. ` +
                    (data.partial ? 'Analysis is limited to the most recent reports. ' : '') +
                    'Counts are calculated by the server from the reports cited by the AI and may not cover every mention.';
                result.hidden = false;
                buttonLabel.textContent = 'Regenerate summary';
            } catch (failure) {
                error.textContent = failure.name === 'AbortError'
                    ? 'The service is taking too long to respond. Please try again.'
                    : (failure instanceof TypeError ? 'Connection interrupted. Please try again.' : failure.message);
                error.hidden = false;
            } finally {
                clearTimeout(timer);
                button.disabled = false;
                loading.hidden = true;
                panel.setAttribute('aria-busy', 'false');
            }
        });
    });
}
