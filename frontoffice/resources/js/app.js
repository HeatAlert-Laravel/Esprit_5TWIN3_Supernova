// Resident pages are server-rendered Blade. Two small progressive enhancements only:
// the mobile navigation toggle and the show/hide password button.

document.addEventListener('DOMContentLoaded', () => {
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
