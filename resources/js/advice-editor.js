import Quill from 'quill';
import '../css/advice-editor.css';

export function initAdviceEditors() {
    document.querySelectorAll('[data-advice-editor]').forEach((wrapper) => {
        const textarea = wrapper.querySelector('textarea');
        const documentInput = wrapper.querySelector('[data-editor-document]');
        const surface = wrapper.querySelector('[data-editor-surface]');
        const toolbar = wrapper.querySelector('[data-editor-toolbar]');
        const heading = wrapper.querySelector('[data-editor-heading]');
        const counter = wrapper.querySelector('[data-editor-counter]');
        const error = wrapper.querySelector('[data-editor-error]');
        const form = wrapper.closest('form');
        const quill = new Quill(surface, {
            formats: ['bold', 'italic', 'header', 'list'],
            modules: { toolbar: false, history: { delay: 1000, userOnly: true } },
        });
        const initial = documentInput.value ? JSON.parse(documentInput.value) : null;
        if (initial) quill.setContents(initial, 'silent');
        else quill.setText(textarea.value, 'silent');
        quill.history.clear();
        surface.hidden = false;
        toolbar.hidden = false;
        wrapper.querySelector('[data-editor-footer]').hidden = false;
        textarea.hidden = true;
        textarea.required = false;
        quill.root.classList.add('ha-article-content');
        quill.root.id = 'contenu-editor';
        quill.root.setAttribute('role', 'textbox');
        quill.root.setAttribute('aria-multiline', 'true');
        quill.root.setAttribute('aria-required', 'true');
        quill.root.setAttribute('aria-describedby', form.querySelector('#contenu_formate-error')
            ? 'contenu-help contenu_formate-error' : 'contenu-help');
        const label = form.querySelector('label[for="contenu"]');
        label.id = 'contenu-label';
        quill.root.setAttribute('aria-labelledby', label.id);
        label.addEventListener('click', () => quill.focus());
        let lastRange = { index: 0, length: 0 };
        const sync = () => {
            documentInput.value = JSON.stringify(quill.getContents());
            textarea.value = quill.getText().replace(/\n+$/, '');
            const words = textarea.value.trim() ? textarea.value.trim().split(/\s+/u).length : 0;
            counter.textContent = wrapper.dataset.counter
                .replace(':words', words).replace(':minutes', Math.max(1, Math.ceil(words / 200)));
            if (!error.hidden) {
                error.hidden = true;
                quill.root.removeAttribute('aria-invalid');
            }
        };
        const updateToolbar = () => {
            const range = quill.getSelection() || lastRange;
            const format = quill.getFormat(range);
            heading.value = [2, 3].includes(format.header) ? String(format.header) : '';
            toolbar.querySelectorAll('[data-editor-format]').forEach((button) => {
                const active = button.dataset.editorValue
                    ? format[button.dataset.editorFormat] === button.dataset.editorValue
                    : !!format[button.dataset.editorFormat];
                button.setAttribute('aria-pressed', String(active));
            });
        };
        const restoreSelection = () => quill.setSelection(lastRange, 'silent');
        toolbar.querySelectorAll('button').forEach((button) => {
            button.addEventListener('mousedown', (event) => event.preventDefault());
            button.addEventListener('click', () => {
                restoreSelection();
                if (button.dataset.editorHistory) {
                    quill.history[button.dataset.editorHistory]();
                } else {
                    const name = button.dataset.editorFormat;
                    const format = quill.getFormat(lastRange);
                    const value = button.dataset.editorValue || true;
                    quill.format(name, format[name] === value ? false : value, 'user');
                }
                quill.focus();
                updateToolbar();
                sync();
            });
        });
        heading.addEventListener('change', () => {
            restoreSelection();
            quill.format('header', heading.value ? Number(heading.value) : false, 'user');
            quill.focus();
            updateToolbar();
            sync();
        });
        quill.on('selection-change', (range) => {
            if (range) lastRange = range;
            updateToolbar();
        });
        quill.on('text-change', () => { sync(); updateToolbar(); });
        form.addEventListener('submit', (event) => {
            sync();
            const text = textarea.value;
            if (!text.trim() || Array.from(text).length > 15000) {
                event.preventDefault();
                error.textContent = !text.trim() ? wrapper.dataset.emptyMessage : wrapper.dataset.lengthMessage;
                error.hidden = false;
                quill.root.setAttribute('aria-invalid', 'true');
                quill.focus();
            }
        });
        sync();
    });
}
