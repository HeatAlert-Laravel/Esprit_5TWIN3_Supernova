// Automatic GET filters for forms marked with `data-auto-filter`.
//  - selects, checkboxes and radios apply immediately on change;
//  - text search applies after a short pause in typing (debounce);
//  - empty values are left out so "Reset" really is the clean index URL;
//  - the page is reloaded with a plain GET, so pagination keeps the query string server-side.
// Without JavaScript the form still works through the <noscript> submit button.
const DEBOUNCE_MS = 400;
const FOCUS_KEY = 'ha-auto-filter-focus';

function targetUrl(form) {
    const params = new URLSearchParams();

    new FormData(form).forEach((value, key) => {
        if (typeof value === 'string' && value.trim() !== '') {
            params.append(key, value.trim());
        }
    });

    const query = params.toString();

    return form.getAttribute('action') + (query ? `?${query}` : '');
}

function apply(form, focusId = null) {
    const url = targetUrl(form);
    const target = new URL(url, window.location.href);

    // Nothing changed (for example Enter without edits): do not reload.
    if (target.pathname + target.search === window.location.pathname + window.location.search) {
        return;
    }

    try {
        if (focusId) {
            sessionStorage.setItem(FOCUS_KEY, focusId);
        }
    } catch (error) {
        // Storage can be blocked; the filter still works, only the focus restore is lost.
    }

    window.location.assign(url);
}

function restoreFocus() {
    let id = null;

    try {
        id = sessionStorage.getItem(FOCUS_KEY);
        sessionStorage.removeItem(FOCUS_KEY);
    } catch (error) {
        return;
    }

    const field = id ? document.getElementById(id) : null;

    if (field) {
        field.focus();
        const end = field.value.length;
        field.setSelectionRange?.(end, end);
    }
}

function init() {
    document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
        let timer;

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(timer);
            apply(form);
        });

        form.addEventListener('change', (event) => {
            if (event.target.matches('select, input[type="checkbox"], input[type="radio"]')) {
                clearTimeout(timer);
                apply(form);
            }
        });

        form.addEventListener('input', (event) => {
            if (event.target.matches('input[type="search"], input[type="text"]')) {
                clearTimeout(timer);
                timer = setTimeout(() => apply(form, event.target.id), DEBOUNCE_MS);
            }
        });
    });

    restoreFocus();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
