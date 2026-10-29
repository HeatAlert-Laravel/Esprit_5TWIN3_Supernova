export function initOutageNotifications() {
    const root = document.querySelector('[data-outage-notifications]');
    if (!root) return;
    const error = root.querySelector('[data-notification-error]');
    const badge = root.querySelector('[data-notification-count]');
    const summary = root.querySelector('summary');
    let pending = false;
    let stopped = false;
    const update = (data) => {
        if (!Number.isInteger(data.unread_count) || typeof data.html !== 'string') throw new Error('Invalid response');
        badge.textContent = data.unread_count > 99 ? '99+' : String(data.unread_count);
        badge.hidden = data.unread_count === 0;
        summary.setAttribute('aria-label', `${data.unread_count} unread outage notifications`);
        // This is our own escaped Blade partial, returned from an authenticated same-origin endpoint.
        root.querySelector('[data-notification-items]').innerHTML = data.html;
        root.querySelector('[data-notification-read-all]').disabled = data.unread_count === 0;
    };
    const refresh = async (form = null) => {
        if (pending || stopped || document.hidden) return;
        pending = true;
        const button = form?.querySelector('button');
        if (button) button.disabled = true;
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(form ? form.action : root.dataset.url, {
                method: form ? 'PATCH' : 'GET', credentials: 'same-origin', signal: controller.signal,
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            if (response.status === 401 || response.status === 403 || response.status === 419) stopped = true;
            if (!response.ok) throw new Error('Notifications unavailable');
            update(await response.json());
            error.hidden = true;
        } catch {
            error.textContent = stopped ? 'Please sign in again to refresh notifications.' : 'Notifications could not be refreshed. Please try again.';
            error.hidden = false;
        } finally {
            clearTimeout(timer);
            pending = false;
            if (button?.isConnected) button.disabled = button.hasAttribute('data-notification-read-all') ? badge.hidden : false;
        }
    };
    root.addEventListener('toggle', () => { if (root.open) refresh(); });
    root.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-notification-read]');
        if (!form) return; // Opening an outage uses its normal POST and redirect, also without JavaScript.
        if (pending || stopped || document.hidden) return; // Keep the normal form submission as a fallback.
        event.preventDefault();
        refresh(form);
    });
    document.addEventListener('click', (event) => { if (!root.contains(event.target)) root.open = false; });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && root.open) { root.open = false; summary.focus(); } });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    setInterval(() => refresh(), 60000);
}
