import test from 'node:test';
import assert from 'node:assert/strict';

class Element {
    constructor() { this.attrs = {}; this.dataset = {}; this.hidden = true; this.value = ''; this.children = []; this.classes = new Set(); }
    addEventListener(name, callback) { this[name] = callback; }
    setAttribute(key, value) { this.attrs[key] = value; }
    getAttribute(key) { return this.attrs[key] ?? null; }
    removeAttribute(key) { delete this.attrs[key]; }
    focus() { this.focused = true; }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = [...children]; }
    get classList() { return { toggle: (key, value) => value ? this.classes.add(key) : this.classes.delete(key) }; }
}
function replyBubble(n) { return n.messages.children.at(-1).children[1].children[1]; }
let article;
globalThis.document = { readyState: 'loading', addEventListener(name, callback) { this[name] = callback; }, querySelectorAll: () => [article], createElement: () => new Element() };
globalThis.FormData = class { set(key, value) { this[key] = value; } };
const { initArticleAssistants } = await import('../../resources/js/advice-assistant.js');

function setup(fetcher = async () => ({ ok: true, json: async () => ({ answer: '<script>not executable</script>', points: ['<b>Plain text</b>'] }) })) {
    const names = ['toggle', 'panel', 'form', 'question', 'question-error', 'status', 'messages', 'conversation', 'welcome', 'close'];
    const n = Object.fromEntries(names.map(name => [name, new Element()]));
    article = { querySelector: selector => n[selector.slice(16, -1)], contains: target => Object.values(n).includes(target) };
    const buttons = [new Element(), new Element(), new Element()];
    n.form.setAttribute('action', '/advice/1/assistant');
    // Real forms expose named controls before native properties. The three action
    // buttons shadow form.action with a RadioNodeList, not the endpoint URL.
    n.form.action = { toString: () => '[object RadioNodeList]' };
    ['summary', 'checklist', 'ask'].forEach((value, index) => { buttons[index].value = value; });
    n.form.requestSubmit = button => { n.form.requestedAction = button.value; };
    n.form.querySelectorAll = () => buttons;
    n.panel.dataset = { error: 'Unavailable', busy: 'AI busy', limit: 'Wait', session: 'Refresh', questionError: 'Enter a question', loading: 'Thinking', summary: 'Summary', checklist: 'Checklist', ask: 'Answer', aiName: 'HeatAlert AI', userName: 'Resident', userInitial: 'R', summaryPrompt: 'Summarize this article.', checklistPrompt: 'Make a checklist.', retry: 'Try again' };
    let requests = 0;
    globalThis.fetch = (...args) => { requests++; return fetcher(...args); };
    initArticleAssistants();
    return { n, buttons, requests: () => requests, submit: action => n.form.submit({ preventDefault() {}, submitter: { value: action } }) };
}

test('opens only on click and renders answers as text and checklist items as usable checkboxes', async () => {
    const { n, submit, requests } = setup();
    assert.equal(n.panel.hidden, true);
    assert.equal(requests(), 0);
    n.toggle.click();
    assert.equal(n.panel.hidden, false);
    assert.equal(n.question.focused, true);
    await submit('checklist');
    assert.equal(replyBubble(n).children[0].textContent, '<script>not executable</script>');
    const label = replyBubble(n).children[1].children[0].children[0];
    assert.equal(label.children[0].type, 'checkbox');
    assert.equal(label.children[1].textContent, '<b>Plain text</b>');
    n.panel.keydown({ key: 'Escape' });
    assert.equal(n.panel.hidden, true);
    assert.equal(n.toggle.attrs['aria-expanded'], 'false');
});

test('blank questions show inline validation and failed requests can be retried', async () => {
    let fail = true;
    const { n, submit, buttons, requests } = setup(async () => {
        if (fail) throw new TypeError('Network failed');
        return { ok: true, json: async () => ({ answer: 'Saved', points: [] }) };
    });
    await submit('ask');
    assert.equal(requests(), 0);
    assert.equal(n.question.attrs['aria-invalid'], 'true');
    n.question.value = 'What should I prepare?';
    n.question.keydown({ key: 'Enter', preventDefault() {} });
    assert.equal(n.form.requestedAction, 'ask');
    n.question.input();
    await submit('ask');
    assert.equal(n.status.textContent, 'Unavailable');
    assert.equal(buttons[0].disabled, false);
    fail = false;
    await replyBubble(n).children[1].click();
    assert.equal(replyBubble(n).children[0].textContent, 'Saved');
    assert.equal(n.messages.children.length, 2);
});

test('pending requests cannot duplicate generation and quota failures preserve the previous result', async () => {
    let resolve;
    const { n, submit, requests } = setup(() => new Promise(done => { resolve = done; }));
    n.messages.append(new Element());
    const pending = submit('summary');
    await submit('checklist');
    assert.equal(requests(), 1);
    resolve({ ok: false, status: 429 });
    await pending;
    assert.equal(n.status.textContent, 'Wait');
    assert.equal(n.messages.children.length, 3);
    assert.equal(n.panel.attrs['aria-busy'], undefined);
});

test('provider overload gets a clear message while unknown errors keep provider details private', async () => {
    for (const [code, expected] of [['assistant_busy', 'AI busy'], ['unknown', 'Unavailable']]) {
        const { n, submit } = setup(async () => ({ ok: false, status: 503,
            json: async () => ({ code, message: 'Private upstream error' }) }));
        await submit('summary');
        assert.equal(n.status.textContent, expected);
    }
});

test('named action buttons cannot shadow the request destination for any assistant action', async () => {
    const requests = [];
    const { n, submit } = setup(async (url, options) => {
        requests.push([url, options.body.action]);
        return { ok: true, json: async () => ({ answer: 'Ready', points: [] }) };
    });
    n.question.value = 'How do I prepare?';
    for (const action of ['summary', 'checklist', 'ask']) await submit(action);
    assert.deepEqual(requests, ['summary', 'checklist', 'ask'].map(action => ['/advice/1/assistant', action]));
    assert.equal(replyBubble(n).children[0].textContent, 'Ready');
});

test('outside clicks dismiss without stealing focus or losing content and inside clicks keep chat open', () => {
    const { n } = setup();
    n.toggle.click();
    n.question.value = 'My question';
    n.messages.append(new Element());
    document.click({ target: n.question });
    assert.equal(n.panel.hidden, false);
    document.click({ target: new Element() });
    assert.equal(n.panel.hidden, true);
    assert.equal(n.toggle.attrs['aria-expanded'], 'false');
    assert.equal(n.toggle.focused, undefined);
    n.toggle.click();
    assert.equal(n.question.value, 'My question');
    assert.equal(n.messages.children.length, 1);
});

test('chat appends named avatars clears sent drafts and supplies recent successful turns for followups', async () => {
    const sent = [];
    const { n, submit } = setup(async (url, options) => {
        sent.push({ question: options.body.question, history: JSON.parse(options.body.history) });
        return { ok: true, json: async () => ({ answer: 'The first point.', points: [] }) };
    });
    n.question.value = 'Summarize it';
    await submit('ask');
    assert.equal(n.question.value, '');
    assert.equal(n.messages.children.length, 2);
    const user = n.messages.children[0];
    assert.equal(user.children[0].textContent, 'R');
    assert.equal(user.children[1].children[0].textContent, 'Resident');
    assert.equal(n.messages.children[1].children[1].children[0].textContent, 'HeatAlert AI');
    n.question.value = 'Explain that point';
    await submit('ask');
    assert.equal(n.messages.children.length, 4);
    assert.equal(n.question.value, '');
    assert.deepEqual(sent[1].history, [{ role: 'user', content: 'Summarize it' }, { role: 'assistant', content: 'The first point.' }]);
});
