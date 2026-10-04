// Resident pages are server-rendered Blade. A few small progressive enhancements only:
// the mobile navigation toggle, the show/hide password button and auto-applying filters.
import './auto-filter';

document.addEventListener('DOMContentLoaded', () => {
    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    let pendingForm = null;

    if (confirmDialog) {
        const message = confirmDialog.querySelector('[data-confirm-message]');
        const close = () => {
            pendingForm = null;
            confirmDialog.hidden = true;
        };

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');
            if (!form || form.dataset.confirmed === 'true') return;

            event.preventDefault();
            pendingForm = form;
            message.textContent = form.dataset.confirm;
            confirmDialog.hidden = false;
            confirmDialog.querySelector('[data-confirm-submit]').focus();
        });

        confirmDialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => button.addEventListener('click', close));
        confirmDialog.querySelector('[data-confirm-submit]').addEventListener('click', () => {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = 'true';
            HTMLFormElement.prototype.submit.call(pendingForm);
            close();
        });
    }

    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.getElementById('main-nav');

    if (toggle && panel) {
        const setOpen = (open) => {
            panel.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
        };

        toggle.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        const input = document.getElementById(button.dataset.togglePassword);
        if (!input) return;

        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.querySelector('[data-icon-show]').toggleAttribute('hidden', show);
            button.querySelector('[data-icon-hide]').toggleAttribute('hidden', !show);
        });
    });
});
