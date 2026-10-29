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
                source.textContent = `${count} signalement${count > 1 ? 's' : ''} cité${count > 1 ? 's' : ''}`;
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
                        401: 'Votre session a expiré. Reconnectez-vous.',
                        403: 'Cette action est réservée aux administrateurs autorisés.',
                        419: 'Votre session a expiré. Actualisez la page puis réessayez.',
                        429: 'Trop de demandes. Patientez une minute avant de réessayer.',
                        503: 'Le résumé IA est momentanément indisponible. Veuillez réessayer plus tard.',
                    };
                    throw new Error(messages[response.status] || (response.status === 409 ? data.message : 'Le résumé n’a pas pu être généré. Veuillez réessayer.'));
                }
                if (data.empty) {
                    error.textContent = data.message;
                    error.hidden = false;
                    return;
                }
                if (!data.summary || !Array.isArray(data.problems) || !Array.isArray(data.attention)) {
                    throw new Error('La réponse reçue est incomplète. Veuillez réessayer.');
                }
                panel.querySelector('[data-summary-count]').textContent = `${data.total} signalement${data.total > 1 ? 's' : ''}`;
                panel.querySelector('[data-summary-text]').textContent = data.summary;
                panel.querySelector('[data-summary-location]').textContent = data.location;
                panel.querySelector('[data-summary-provider]').textContent = `Généré avec ${data.provider}`;
                renderList('[data-summary-problems]', data.problems, 'Aucun problème précis exploitable dans les descriptions.');
                renderList('[data-summary-attention]', data.attention, 'Aucun point particulier relevé.');
                panel.querySelector('[data-summary-note]').textContent =
                    `${data.analyzed} description(s) analysée(s) avec ${data.provider}. ` +
                    (data.partial ? 'Analyse limitée aux signalements les plus récents. ' : '') +
                    'Les décomptes sont calculés par le serveur à partir des signalements cités par l’IA ; ils ne constituent pas un recensement exhaustif des problèmes.';
                result.hidden = false;
                buttonLabel.textContent = 'Régénérer le résumé';
            } catch (failure) {
                error.textContent = failure.name === 'AbortError'
                    ? 'Le service met trop de temps à répondre. Veuillez réessayer.'
                    : (failure instanceof TypeError ? 'Connexion interrompue. Veuillez réessayer.' : failure.message);
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
