export function initAdviceVotes() {
    document.querySelectorAll('[data-advice-votes]').forEach((widget) => {
        const form = widget.querySelector('[data-vote-form]');
        if (!form) return;
        const buttons = [...form.querySelectorAll('[data-vote-direction]')];
        const score = form.querySelector('[data-vote-score]');
        const feedback = widget.querySelector('[data-vote-feedback]');
        let pending = false;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = event.submitter;
            if (pending || !buttons.includes(button)) return;
            const value = button.value;
            const body = new FormData(form);
            body.set('value', value);
            pending = true;
            buttons.forEach((item) => { item.disabled = true; });
            form.setAttribute('aria-busy', 'true');
            feedback.textContent = '';
            feedback.classList.add('sr-only');
            try {
                const response = await fetch(form.action, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body,
                });
                if (response.status === 401) {
                    window.location.assign(widget.dataset.signInUrl);
                    return;
                }
                if (!response.ok) {
                    throw new Error(response.status === 419 ? widget.dataset.sessionError
                        : (response.status === 429 ? widget.dataset.limitError : widget.dataset.error));
                }
                const result = await response.json();
                score.textContent = result.score;
                score.dataset.vote = result.vote;
                buttons.forEach((item) => {
                    const direction = Number(item.dataset.voteDirection);
                    item.value = String(result.vote === direction ? 0 : direction);
                    item.setAttribute('aria-pressed', String(result.vote === direction));
                });
                feedback.textContent = result.message;
            } catch (error) {
                feedback.classList.remove('sr-only');
                feedback.textContent = error instanceof TypeError ? widget.dataset.error : (error.message || widget.dataset.error);
            } finally {
                pending = false;
                buttons.forEach((item) => { item.disabled = false; });
                form.removeAttribute('aria-busy');
            }
        });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAdviceVotes);
else initAdviceVotes();
