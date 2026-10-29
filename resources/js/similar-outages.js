export function initSimilarOutages() {
    document.querySelectorAll('[data-similar-outages]').forEach((panel) => {
        const form = panel.closest('form');
        const description = form.querySelector('[name="description"]');
        const address = form.querySelector('[name="adresse"]');
        const associated = form.querySelector('[name="coupure_id"]');
        const quartier = panel.querySelector('[data-similar-quartier]');
        const search = panel.querySelector('[data-similar-search]');
        const loading = panel.querySelector('[data-similar-loading]');
        const error = panel.querySelector('[data-similar-error]');
        const results = panel.querySelector('[data-similar-results]');
        const message = panel.querySelector('[data-similar-message]');
        const cards = panel.querySelector('[data-similar-cards]');
        const selection = panel.querySelector('[data-similar-selection]');
        let version = 0;
        let pending = null;
        let chosenSuggestion = null;
        const snapshot = () => JSON.stringify([quartier.value, address.value, description.value]);
        const invalidate = () => {
            version++;
            pending?.abort();
            pending = null;
            loading.hidden = true;
            search.disabled = false;
            results.hidden = true;
            error.hidden = true;
            if (chosenSuggestion !== null && associated.value === chosenSuggestion) {
                associated.value = '';
                selection.textContent = 'Votre saisie a changé : la coupure suggérée a été désélectionnée. Vous pouvez relancer la recherche ou choisir manuellement.';
                selection.hidden = false;
            } else selection.hidden = true;
            chosenSuggestion = null;
        };
        description.addEventListener('input', invalidate);
        address.addEventListener('input', invalidate);
        quartier.addEventListener('change', invalidate);
        associated.addEventListener('change', () => {
            chosenSuggestion = null;
            selection.hidden = true;
            cards.querySelectorAll('[data-select-similar]').forEach((button) => {
                button.disabled = false;
                button.textContent = 'Sélectionner cette coupure';
                button.closest('article').classList.remove('is-selected');
            });
        });
        panel.querySelector('[data-similar-ignore]').addEventListener('click', invalidate);
        form.addEventListener('submit', () => {
            version++;
            pending?.abort();
        });
        const text = (tag, className, content) => {
            const element = document.createElement(tag);
            element.className = className;
            element.textContent = content;
            return element;
        };
        const render = (data) => {
            message.textContent = data.message;
            panel.dataset.mode = data.mode;
            cards.replaceChildren();
            data.suggestions.forEach((outage) => {
                const card = document.createElement('article');
                card.className = 'ha-similar__card';
                const head = document.createElement('div');
                head.className = 'ha-similar__card-head';
                head.append(text('h4', '', outage.quartier), text('span', 'ha-badge ha-badge--warning', 'Active'));
                card.append(head, text('p', 'ha-similar__date', `Coupure #${outage.id} · Début : ${outage.date_debut}`));
                card.append(text('p', 'ha-similar__description', outage.description || 'Aucune description disponible.'));
                card.append(text('p', 'ha-similar__reason', outage.reason));
                const button = text('button', 'ha-btn ha-btn--outline ha-btn--sm', 'Sélectionner cette coupure');
                button.type = 'button';
                button.dataset.selectSimilar = '';
                button.addEventListener('click', () => {
                    const id = String(outage.id);
                    if (!Array.from(associated.options).some((option) => option.value === id)) {
                        associated.add(new Option(`#${outage.id} — ${outage.quartier} — ${outage.date_debut} (active)`, id));
                    }
                    associated.value = id;
                    associated.dispatchEvent(new Event('change', { bubbles: true }));
                    chosenSuggestion = id;
                    button.disabled = true;
                    button.textContent = 'Coupure sélectionnée';
                    card.classList.add('is-selected');
                    selection.textContent = `Coupure #${outage.id} sélectionnée dans le champ « Coupure associée ». Votre signalement n’a pas encore été envoyé.`;
                    selection.hidden = false;
                });
                card.append(button);
                cards.append(card);
            });
            results.hidden = false;
        };
        search.addEventListener('click', async () => {
            if (search.disabled) return;
            error.hidden = true;
            if (!quartier.value || description.value.trim().length < 20) {
                error.textContent = !quartier.value ? 'Choisissez votre quartier avant de lancer la recherche.'
                    : 'Précisez votre problème avec au moins 20 caractères avant de lancer la recherche.';
                error.hidden = false;
                (!quartier.value ? quartier : description).focus();
                return;
            }
            const current = ++version;
            const draft = snapshot();
            const controller = new AbortController();
            pending = controller;
            const timer = setTimeout(() => controller.abort(), 35000);
            search.disabled = true;
            loading.hidden = false;
            results.hidden = true;
            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'POST', credentials: 'same-origin', signal: controller.signal,
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ quartier_id: quartier.value, description: description.value, adresse: address.value }),
                });
                const data = await response.json().catch(() => ({}));
                if (current !== version || draft !== snapshot()) return;
                if (!response.ok) {
                    const messages = {
                        401: 'Connectez-vous pour utiliser cette aide.',
                        403: 'Cette recherche est réservée aux utilisateurs résidents autorisés.',
                        419: 'Votre session a expiré. Actualisez la page pour relancer la recherche.',
                        429: 'Trop de recherches rapprochées. Patientez une minute, ou envoyez votre signalement normalement.',
                        409: 'Une recherche est déjà en cours. Patientez quelques instants.',
                    };
                    const validation = response.status === 422 ? Object.values(data.errors || {}).flat()[0] : null;
                    throw new Error(validation || messages[response.status] || 'La recherche est indisponible. Vous pouvez envoyer votre signalement normalement.');
                }
                if (!Array.isArray(data.suggestions) || typeof data.message !== 'string') {
                    throw new Error('La recherche n’a pas abouti. Vous pouvez poursuivre votre signalement normalement.');
                }
                render(data);
            } catch (failure) {
                if (current !== version) return;
                error.textContent = failure.name === 'AbortError'
                    ? 'La recherche prend trop de temps. Réessayez ou poursuivez votre signalement normalement.'
                    : (failure instanceof TypeError ? 'Connexion interrompue. Votre formulaire reste disponible.' : failure.message);
                error.hidden = false;
            } finally {
                clearTimeout(timer);
                if (current === version) {
                    pending = null;
                    search.disabled = false;
                    loading.hidden = true;
                }
            }
        });
    });
}
