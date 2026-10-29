// Interactive weather map and tabs for the resident pages.
//  - /weather-alerts: Leaflet map of neighborhoods colored by vigilance level, linked to the list on its right;
//  - /neighborhoods/{id}: single-neighborhood mini map and the "Active / Upcoming / Past" tabs.
// Leaflet itself is loaded from a CDN by the Blade view (window.L). If it is missing the list still works.
const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const LEVEL_COLORS = { vert: '#2F855A', jaune: '#D69E2E', orange: '#E8590C', rouge: '#C53030' };

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function pinIcon(point) {
    // Only known level names and a rounded number ever reach the markup.
    const level = LEVEL_COLORS[point.niveau] ? point.niveau : 'vert';
    const label = Number.isFinite(Number(point.temperature)) && point.temperature !== null ? `${Math.round(Number(point.temperature))}°` : '';
    return window.L.divIcon({
        className: 'wx-pin-wrap',
        html: `<span class="wx-pin wx-pin--${level}" style="--pin: ${LEVEL_COLORS[level]}"><b>${label}</b></span>`,
        iconSize: [46, 46],
        iconAnchor: [23, 23],
        popupAnchor: [0, -22],
    });
}

function popupContent(point) {
    const box = el('div', 'wx-popup');
    box.append(el('strong', 'wx-popup__name', point.nom), el('span', 'wx-popup__city', point.ville));

    const level = el('span', `wx-popup__level wx-popup__level--${point.niveau}`, point.active > 0 ? `${point.label} level` : 'No active alert');
    box.append(level);

    if (point.title) box.append(el('p', 'wx-popup__title', point.title));
    if (point.active > 1) box.append(el('p', 'wx-popup__more', `${point.active} alerts active today`));
    if (point.outage) box.append(el('p', 'wx-popup__outage', 'Power outage risk'));

    if (point.url) {
        const link = el('a', 'wx-popup__link', 'View neighborhood →');
        link.href = point.url;
        box.append(link);
    }

    return box;
}

function distanceKm(lat1, lng1, lat2, lng2) {
    const rad = (deg) => (deg * Math.PI) / 180;
    const a = Math.sin(rad(lat2 - lat1) / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(rad(lng2 - lng1) / 2) ** 2;
    return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function initMap(container) {
    const fallback = container.parentElement.querySelector('[data-wx-map-fallback]');

    if (!window.L) {
        container.hidden = true;
        if (fallback) fallback.hidden = false;
        return;
    }

    let points = [];
    try {
        points = JSON.parse(container.dataset.points || '[]');
    } catch {
        points = [];
    }

    const mini = container.hasAttribute('data-mini');
    const L = window.L;
    const map = L.map(container, { scrollWheelZoom: false, zoomControl: !mini, dragging: !mini || !L.Browser.mobile, attributionControl: true });
    L.tileLayer(TILES, { attribution: ATTRIBUTION, maxZoom: 19 }).addTo(map);

    const markers = new Map();
    const section = container.closest('[data-wx-map-section]');
    const list = section?.querySelector('[data-wx-list]');
    const status = section?.querySelector('[data-wx-status]');

    const setActive = (id) => {
        markers.forEach((marker, key) => marker.getElement()?.classList.toggle('is-active', key === id));
        list?.querySelectorAll('[data-wx-focus]').forEach((button) => {
            const active = Number(button.dataset.wxFocus) === id;
            button.classList.toggle('is-active', active);
            if (active) button.setAttribute('aria-current', 'true');
            else button.removeAttribute('aria-current');
        });
    };

    const focusPoint = (id, { fly = true } = {}) => {
        const marker = markers.get(id);
        const point = points.find((candidate) => candidate.id === id);
        if (!marker || !point) return;
        setActive(id);
        if (fly) map.flyTo([point.lat, point.lng], Math.max(map.getZoom(), 14), { duration: 0.6 });
        marker.openPopup();
    };

    points.forEach((point) => {
        const marker = L.marker([point.lat, point.lng], { icon: pinIcon(point), title: `${point.nom} — ${point.label}`, keyboard: true, riseOnHover: true });
        marker.bindPopup(() => popupContent(point), { closeButton: true, autoPanPadding: [24, 24] });
        marker.on('click', () => {
            setActive(point.id);
            const button = list?.querySelector(`[data-wx-focus="${point.id}"]`);
            if (button && list) list.scrollTo({ top: button.offsetTop - list.offsetTop - 8, behavior: 'smooth' });
        });
        marker.addTo(map);
        markers.set(point.id, marker);
    });

    const fitAll = () => {
        if (points.length === 0) {
            map.setView([36.8065, 10.1815], 11);
        } else if (points.length === 1) {
            map.setView([points[0].lat, points[0].lng], mini ? 13 : 14);
        } else {
            map.fitBounds(points.map((point) => [point.lat, point.lng]), { padding: [40, 40], maxZoom: 14 });
        }
    };
    fitAll();

    if (mini) {
        L.circle([points[0].lat, points[0].lng], { radius: 700, color: LEVEL_COLORS[points[0].niveau], weight: 1, fillOpacity: 0.12 }).addTo(map);
        return;
    }

    // Scrolling the page must not zoom the map by accident; enable wheel zoom only after a click on the map.
    map.on('click focus', () => map.scrollWheelZoom.enable());
    map.on('mouseout', () => map.scrollWheelZoom.disable());

    list?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-wx-focus]');
        if (button) focusPoint(Number(button.dataset.wxFocus));
    });
    list?.addEventListener('mouseover', (event) => {
        const button = event.target.closest('[data-wx-focus]');
        if (button) markers.get(Number(button.dataset.wxFocus))?.getElement()?.classList.add('is-hover');
    });
    list?.addEventListener('mouseout', (event) => {
        const button = event.target.closest('[data-wx-focus]');
        if (button) markers.get(Number(button.dataset.wxFocus))?.getElement()?.classList.remove('is-hover');
    });

    section?.querySelector('[data-wx-reset]')?.addEventListener('click', () => {
        map.closePopup();
        setActive(null);
        fitAll();
        if (status) status.hidden = true;
    });

    const say = (message) => {
        if (!status) return;
        status.hidden = message === '';
        status.textContent = message;
    };

    section?.querySelector('[data-wx-locate]')?.addEventListener('click', (event) => {
        if (!navigator.geolocation || points.length === 0) {
            say('Location is not available in this browser.');
            return;
        }

        const button = event.currentTarget;
        button.disabled = true;
        say('Looking for the nearest neighborhood…');

        // The position stays in the browser: it is only compared with the neighborhoods shown on the map.
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                button.disabled = false;
                const nearest = points
                    .map((point) => ({ point, km: distanceKm(coords.latitude, coords.longitude, point.lat, point.lng) }))
                    .sort((a, b) => a.km - b.km)[0];
                say(`Nearest neighborhood: ${nearest.point.nom} (${nearest.km.toFixed(1)} km away).`);
                focusPoint(nearest.point.id);
            },
            () => {
                button.disabled = false;
                say('Your location could not be read. Allow location access or pick a neighborhood in the list.');
            },
            { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
        );
    });

    // The map sits in a flexible layout: recompute its size once everything is painted.
    setTimeout(() => map.invalidateSize(), 150);
}

function initTabs(root) {
    const tabs = Array.from(root.querySelectorAll('[data-wx-tab]'));
    const panels = Array.from(root.querySelectorAll('[data-wx-panel]'));

    const show = (key) => {
        tabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.wxTab === key ? 'true' : 'false'));
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.wxPanel !== key;
        });
    };

    root.classList.add('is-enhanced');
    show(root.dataset.defaultTab || tabs[0]?.dataset.wxTab);

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => show(tab.dataset.wxTab));
        tab.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
            next.focus();
            show(next.dataset.wxTab);
        });
    });
}

export function initWeatherPages() {
    document.querySelectorAll('[data-wx-map]').forEach(initMap);
    document.querySelectorAll('[data-wx-tabs]').forEach(initTabs);
}
