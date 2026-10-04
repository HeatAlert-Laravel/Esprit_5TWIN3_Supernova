function initAdviceBookmarks() {
    document.querySelectorAll('form[data-advice-bookmark]').forEach((form) => {
        const button = form.querySelector('button');
        const feedback = form.querySelector('[data-bookmark-feedback]');
        const method = form.querySelector('[name="_method"]');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (button.disabled) return;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            feedback.textContent = '';
            feedback.classList.add('sr-only');
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                if (response.status === 401) {
                    window.location.assign(form.dataset.signInUrl);
                    return;
                }
                if (!response.ok) {
                    throw new Error(response.status === 419 ? form.dataset.sessionError : form.dataset.error);
                }
                const result = await response.json();
                button.setAttribute('aria-pressed', String(result.saved));
                button.setAttribute('aria-label', result.saved ? form.dataset.removeLabel : form.dataset.saveLabel);
                form.querySelector('[data-bookmark-label]').textContent = result.saved ? form.dataset.savedText : form.dataset.saveText;
                form.querySelector('[data-bookmark-icon]').hidden = result.saved;
                form.querySelector('[data-bookmark-check]').hidden = !result.saved;
                method.value = result.saved ? 'DELETE' : 'PUT';
                feedback.textContent = result.message;
            } catch (error) {
                feedback.classList.remove('sr-only');
                feedback.textContent = error.message || form.dataset.error;
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAdviceBookmarks);
else initAdviceBookmarks();
