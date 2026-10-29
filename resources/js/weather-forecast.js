// "Live forecast" panel of the weather alert form.
// Loads the 7-day Open-Meteo forecast of the selected neighborhood from the admin endpoint and lets the
// administrator pre-fill the alert fields from one day. Nothing is saved until the form is submitted.
const LEVEL_LABELS = { vert: 'Green', jaune: 'Yellow', orange: 'Orange', rouge: 'Red' };
const LEVEL_BADGES = { vert: 'level-green', jaune: 'level-yellow', orange: 'level-orange', rouge: 'level-red' };

function setField(form, name, value) {
    const field = form.elements[name];
    if (!field) return;
    if (field.type === 'checkbox') {
        field.checked = Boolean(value);
    } else {
        field.value = value ?? '';
    }
    field.dispatchEvent(new Event('input', { bubbles: true }));
}

function fillFromDay(form, day, quartierName) {
    setField(form, 'niveau', day.niveau);
    setField(form, 'type', day.niveau === 'vert' ? 'autre' : 'canicule');
    setField(form, 'temperature_max', day.temperature_max);
    setField(form, 'temperature_min', day.temperature_min);
    setField(form, 'temperature_ressentie', day.temperature_ressentie);
    setField(form, 'humidite', day.humidite);
    setField(form, 'indice_uv', day.indice_uv);
    setField(form, 'vitesse_vent', day.vitesse_vent);
    setField(form, 'risque_coupure', day.risque_coupure);
    setField(form, 'source', 'Open-Meteo');
    setField(form, 'date_debut', day.date);
    setField(form, 'date_fin', day.date);

    const title = form.elements.titre;
    if (title && title.value.trim() === '') {
        setField(form, 'titre', `${LEVEL_LABELS[day.niveau]} heat watch - ${quartierName}`);
    }
}

function renderDays(form, panel, data, quartierName) {
    const list = panel.querySelector('[data-forecast-days]');
    list.replaceChildren();

    data.days.forEach((day) => {
        const row = document.createElement('div');
        row.className = 'ha-forecast__day';

        const date = document.createElement('span');
        date.className = 'ha-forecast__date';
        date.textContent = new Date(`${day.date}T12:00:00`).toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: 'short' });

        const values = document.createElement('span');
        values.className = 'ha-forecast__values';
        values.textContent = `${day.temperature_min}° / ${day.temperature_max}° (feels ${day.temperature_ressentie}°) · ${day.humidite}% humidity · UV ${day.indice_uv} · ${day.vitesse_vent} km/h`;

        const badge = document.createElement('span');
        badge.className = `ha-badge ha-badge--${LEVEL_BADGES[day.niveau]}`;
        badge.textContent = LEVEL_LABELS[day.niveau];

        const use = document.createElement('button');
        use.type = 'button';
        use.className = 'ha-btn ha-btn--outline ha-btn--sm';
        use.textContent = 'Use this day';
        use.addEventListener('click', () => fillFromDay(form, day, quartierName));

        row.append(date, values, badge, use);
        list.append(row);
    });
}

export function initWeatherForecast() {
    document.querySelectorAll('[data-weather-forecast]').forEach((panel) => {
        const form = panel.closest('form');
        const select = form?.elements.quartier_id;
        const button = panel.querySelector('[data-forecast-load]');
        const status = panel.querySelector('[data-forecast-status]');
        if (!form || !select || !button) return;

        const show = (message, isError = false) => {
            status.hidden = message === '';
            status.textContent = message;
            status.classList.toggle('ha-forecast__status--error', isError);
        };

        button.addEventListener('click', async () => {
            if (!select.value) {
                show('Choose a neighborhood first.', true);
                return;
            }

            const quartierName = select.selectedOptions[0].textContent.split(' - ')[0].trim();
            button.disabled = true;
            show('Loading forecast…');
            panel.querySelector('[data-forecast-days]').replaceChildren();

            try {
                const response = await fetch(panel.dataset.urlTemplate.replace('/0/', `/${select.value}/`), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    show(data.message || 'The forecast could not be loaded.', true);
                    return;
                }

                show(`Forecast for ${quartierName} — source: ${data.source}`);
                renderDays(form, panel, data, quartierName);
            } catch {
                show('The forecast could not be loaded.', true);
            } finally {
                button.disabled = false;
            }
        });
    });
}
