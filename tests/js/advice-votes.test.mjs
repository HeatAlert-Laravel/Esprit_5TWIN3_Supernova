import test from 'node:test';
import assert from 'node:assert/strict';

class Element {
    constructor() { this.dataset = {}; this.attrs = {}; this.classes = new Set(['sr-only']); }
    addEventListener(name, listener) { this[name] = listener; }
    setAttribute(key, value) { this.attrs[key] = value; }
    removeAttribute(key) { delete this.attrs[key]; }
    get classList() { return { add: key => this.classes.add(key), remove: key => this.classes.delete(key) }; }
}
let widget;
globalThis.document = { readyState: 'loading', addEventListener() {}, querySelectorAll: () => [widget] };
globalThis.FormData = class { set(key, value) { this[key] = value; } };
const { initAdviceVotes } = await import('../../resources/js/advice-votes.js');

function setup(fetcher) {
    widget = new Element();
    widget.dataset = { error: 'Try again', sessionError: 'Refresh', limitError: 'Wait', signInUrl: '/advice/1/vote' };
    const form = new Element();
    const score = new Element();
    score.textContent = 4;
    const feedback = new Element();
    const buttons = [1, -1].map(direction => Object.assign(new Element(), {
        dataset: { voteDirection: String(direction) }, value: String(direction),
    }));
    widget.querySelector = selector => selector === '[data-vote-form]' ? form : feedback;
    form.querySelectorAll = () => buttons;
    form.querySelector = () => score;
    form.action = '/advice/1/vote';
    let requests = 0;
    globalThis.fetch = (...args) => { requests++; return fetcher(...args); };
    let redirect;
    globalThis.window = { location: { assign: url => { redirect = url; } } };
    initAdviceVotes();
    return { form, score, feedback, buttons, requests: () => requests, redirect: () => redirect,
        click: index => form.submit({ preventDefault() {}, submitter: buttons[index] }) };
}

test('upvote, removal and switching send explicit state and update both arrows', async () => {
    const values = [];
    const ui = setup(async (url, options) => {
        const vote = Number(options.body.value);
        values.push(vote);
        return { ok: true, json: async () => ({ vote, score: 4 + vote, message: 'Saved' }) };
    });
    await ui.click(0);
    assert.equal(ui.buttons[0].attrs['aria-pressed'], 'true');
    assert.equal(ui.buttons[0].value, '0');
    assert.equal(ui.score.textContent, 5);
    await ui.click(0);
    assert.equal(ui.buttons[0].attrs['aria-pressed'], 'false');
    await ui.click(0);
    await ui.click(1);
    assert.deepEqual(values, [1, 0, 1, -1]);
    assert.equal(ui.buttons[0].attrs['aria-pressed'], 'false');
    assert.equal(ui.buttons[1].attrs['aria-pressed'], 'true');
    assert.equal(ui.score.dataset.vote, -1);
});

test('pending requests block duplicate submissions and network errors preserve the vote for retry', async () => {
    let reject;
    const ui = setup(() => new Promise((resolve, fail) => { reject = fail; }));
    const pending = ui.click(0);
    await ui.click(1);
    assert.equal(ui.requests(), 1);
    assert.equal(ui.buttons[0].disabled, true);
    reject(new TypeError('Network failure'));
    await pending;
    assert.equal(ui.score.textContent, 4);
    assert.equal(ui.buttons[0].value, '1');
    assert.equal(ui.buttons[0].disabled, false);
    assert.equal(ui.feedback.textContent, 'Try again');
    assert.equal(ui.feedback.classes.has('sr-only'), false);
});

test('expired sessions and rate limits explain recovery without changing the score', async () => {
    for (const [status, message] of [[419, 'Refresh'], [429, 'Wait']]) {
        const ui = setup(async () => ({ ok: false, status }));
        await ui.click(0);
        assert.equal(ui.feedback.textContent, message);
        assert.equal(ui.score.textContent, 4);
    }
    const ui = setup(async () => ({ ok: false, status: 401 }));
    await ui.click(0);
    assert.equal(ui.redirect(), '/advice/1/vote');
    assert.equal(ui.score.textContent, 4);
});
