export function initArticleAssistants() {
    document.querySelectorAll('[data-article-assistant]').forEach((article) => {
        const node = name => article.querySelector(`[data-assistant-${name}]`);
        const toggle = node('toggle'), panel = node('panel'), form = node('form');
        const question = node('question'), error = node('question-error'), status = node('status');
        const messages = node('messages'), conversation = node('conversation');
        const buttons = [...form.querySelectorAll('button')];
        const history = [];
        let pending = false;
        const element = (tag, className, text) => {
            const item = document.createElement(tag);
            if (className) item.className = className;
            if (text !== undefined) item.textContent = text;
            return item;
        };
        const scrollToLatest = () => { conversation.scrollTop = conversation.scrollHeight; };
        const message = (role, text) => {
            const row = element('div', `ha-chat-message ha-chat-message--${role}`);
            const avatar = element('span', `ha-chat-avatar ha-chat-avatar--${role}`, role === 'ai' ? '✦' : panel.dataset.userInitial);
            avatar.setAttribute('aria-hidden', 'true');
            const content = element('div', 'ha-chat-content');
            const name = element('span', 'ha-chat-name', role === 'ai' ? panel.dataset.aiName : panel.dataset.userName);
            const bubble = element('div', 'ha-chat-bubble');
            bubble.append(element('p', '', text));
            content.append(name, bubble);
            row.append(avatar, content);
            messages.append(row);
            return { row, bubble };
        };
        const close = (restoreFocus = true) => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus) toggle.focus();
        };
        const clearError = () => { error.hidden = true; question.removeAttribute('aria-invalid'); };
        toggle.disabled = false;
        toggle.addEventListener('click', () => {
            if (!panel.hidden) return close();
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            question.focus();
        });
        node('close').addEventListener('click', () => close());
        document.addEventListener('click', event => {
            if (!panel.hidden && !article.contains(event.target)) close(false);
        });
        panel.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
        question.addEventListener('input', clearError);
        question.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                if (!pending) form.requestSubmit(buttons.find(button => button.value === 'ask'));
            }
        });
        const send = async (action, text, retry = null) => {
            if (pending) return;
            clearError();
            if (action === 'ask' && (!text || text.length > 500)) {
                error.textContent = panel.dataset.questionError;
                error.hidden = false;
                question.setAttribute('aria-invalid', 'true');
                question.focus();
                return;
            }
            const prompt = action === 'ask' ? text : panel.dataset[`${action}Prompt`];
            const body = new FormData(form);
            body.set('action', action);
            body.set('question', action === 'ask' ? text : '');
            body.set('history', JSON.stringify(action === 'ask' ? history.slice(-6) : []));
            if (!retry) {
                message('user', prompt);
                if (action === 'ask') question.value = '';
            }
            const reply = retry || message('ai', panel.dataset.loading);
            reply.bubble.replaceChildren(element('p', 'ha-chat-thinking', panel.dataset.loading));
            reply.row.setAttribute('aria-busy', 'true');
            node('welcome').hidden = true;
            pending = true;
            buttons.forEach(button => { button.disabled = true; });
            status.textContent = panel.dataset.loading;
            scrollToLatest();
            try {
                const response = await fetch(form.getAttribute('action'), { method: 'POST', credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body });
                if (!response.ok) {
                    const problem = response.status === 503 ? await response.json().catch(() => ({})) : {};
                    throw new Error(response.status === 419 ? panel.dataset.session
                        : ([429, 409].includes(response.status) ? panel.dataset.limit
                            : (problem.code === 'assistant_busy' ? panel.dataset.busy : panel.dataset.error)));
                }
                const data = await response.json();
                if (typeof data.answer !== 'string' || !Array.isArray(data.points)
                    || !data.points.every(point => typeof point === 'string')) throw new Error(panel.dataset.error);
                reply.bubble.replaceChildren(element('p', '', data.answer));
                const points = element('ul', action === 'checklist' ? 'ha-assistant-checklist' : '');
                data.points.forEach(text => {
                    const item = element('li');
                    if (action === 'checklist') {
                        const label = element('label');
                        const check = element('input');
                        check.type = 'checkbox';
                        label.append(check, element('span', '', text));
                        item.append(label);
                    } else item.textContent = text;
                    points.append(item);
                });
                if (data.points.length) reply.bubble.append(points);
                history.push({ role: 'user', content: prompt }, { role: 'assistant', content: [data.answer, ...data.points].join('\n').slice(0, 8000) });
                history.splice(0, Math.max(0, history.length - 6));
                status.textContent = panel.dataset[action];
            } catch (failure) {
                const text = failure instanceof TypeError ? panel.dataset.error : (failure.message || panel.dataset.error);
                const again = element('button', 'ha-btn ha-btn--outline ha-btn--sm', panel.dataset.retry);
                again.type = 'button';
                again.addEventListener('click', () => send(action, action === 'ask' ? prompt : '', reply));
                reply.bubble.replaceChildren(element('p', 'ha-assistant-error', text), again);
                status.textContent = text;
            } finally {
                pending = false;
                buttons.forEach(button => { button.disabled = false; });
                reply.row.removeAttribute('aria-busy');
                scrollToLatest();
            }
        };
        form.addEventListener('submit', event => {
            event.preventDefault();
            return send(event.submitter?.value || 'ask', question.value.trim());
        });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initArticleAssistants);
else initArticleAssistants();
