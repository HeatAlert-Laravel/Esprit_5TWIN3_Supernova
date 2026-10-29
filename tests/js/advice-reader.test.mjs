import test from 'node:test';
import assert from 'node:assert/strict';

class Element {
    constructor() { this.listeners = {}; this.attrs = {}; this.hidden = true; this.value = ''; this.classes = new Set(); }
    addEventListener(event, callback) { (this.listeners[event] ??= []).push(callback); }
    async fire(event, extra = {}) {
        for (const callback of this.listeners[event] ?? []) await callback({ preventDefault() {}, ...extra });
    }
    setAttribute(key, value) { this.attrs[key] = String(value); }
    removeAttribute(key) { delete this.attrs[key]; }
    getAttribute(key) { return this.attrs[key] ?? null; }
    get classList() { return { toggle: (key, enabled) => enabled ? this.classes.add(key) : this.classes.delete(key) }; }
    contains(target) { return target === this; }
    focus() { this.focused = true; }
}

let current;
globalThis.document = {
    readyState: 'loading',
    addEventListener() {},
    querySelectorAll: () => [current.reader],
};
globalThis.window = { location: { href: 'http://localhost/advice/1', origin: 'http://localhost' }, addEventListener() {} };
globalThis.FormData = class {};
const { initAdviceReaders } = await import('../../resources/js/advice-reader.js');

function setup(fetcher = async () => ({ ok: true, json: async () => ({ url: 'http://localhost/audio/female' }) })) {
    const names = ['form', 'voice', 'speed', 'load', 'status', 'audio', 'stop', 'time', 'options',
        'seek', 'seek-field', 'play-icon', 'pause-icon'];
    const nodes = Object.fromEntries(names.map(name => [name, new Element()]));
    const reader = new Element();
    reader.querySelector = selector => nodes[selector.slice(13, -1)];
    reader.dataset = { listenLabel: 'Listen', pauseLabel: 'Pause', ready: 'Ready', loading: 'Loading',
        playing: 'Playing', paused: 'Paused', finished: 'Finished', error: 'Unavailable' };
    const summary = new Element();
    nodes.options.querySelector = () => summary;
    nodes.voice.value = 'female';
    nodes.speed.value = '1';
    const audio = nodes.audio;
    audio.paused = true;
    audio.ended = false;
    audio.currentTime = 0;
    audio.duration = NaN;
    audio.playCalls = 0;
    Object.defineProperty(audio, 'src', { set(value) { audio.setAttribute('src', value); } });
    audio.play = () => { audio.playCalls++; audio.paused = false; audio.fire('play'); return Promise.resolve(); };
    audio.pause = () => { if (!audio.paused) { audio.paused = true; audio.fire('pause'); } };
    audio.load = () => { audio.currentTime = 0; audio.duration = NaN; };
    let requests = 0;
    globalThis.fetch = (...args) => { requests++; return fetcher(...args); };
    current = { reader, nodes, summary, requests: () => requests };
    initAdviceReaders();
    return current;
}

test('one button loads plays pauses and resumes without regenerating or resetting playback', async () => {
    const { nodes: n, requests } = setup();
    await n.form.fire('submit');
    assert.equal(requests(), 1);
    assert.equal(n.load.getAttribute('aria-label'), 'Pause');
    n.audio.currentTime = 12;
    await n.form.fire('submit');
    assert.equal(n.audio.paused, true);
    assert.equal(n.load.getAttribute('aria-label'), 'Listen');
    await n.form.fire('submit');
    assert.equal(n.audio.currentTime, 12);
    assert.equal(n.audio.paused, false);
    assert.equal(requests(), 1);
    await n.stop.fire('click');
    assert.equal(n.audio.currentTime, 0);
    assert.equal(n.audio.paused, true);
});

test('voice changes stop playback and previously selected voices reuse audio', async () => {
    const { nodes: n, requests } = setup();
    await n.form.fire('submit');
    n.voice.value = 'male';
    await n.voice.fire('change');
    assert.equal(n.audio.paused, true);
    assert.equal(n.audio.getAttribute('src'), null);
    await n.form.fire('submit');
    n.voice.value = 'female';
    await n.voice.fire('change');
    await n.form.fire('submit');
    assert.equal(requests(), 2);
    assert.equal(n.audio.paused, false);
});

test('speed seeking timer and Escape keep the small settings menu usable', async () => {
    const { nodes: n, summary } = setup();
    await n.form.fire('submit');
    n.audio.duration = 90;
    n.audio.currentTime = 10;
    await n.audio.fire('loadedmetadata');
    assert.equal(n.time.textContent, '0:10 / 1:30');
    assert.equal(n['seek-field'].hidden, false);
    n.speed.value = '1.5';
    await n.speed.fire('change');
    assert.equal(n.audio.playbackRate, 1.5);
    n.seek.value = '35';
    await n.seek.fire('input');
    assert.equal(n.audio.currentTime, 35);
    n.options.open = true;
    await current.reader.fire('keydown', { key: 'Escape' });
    assert.equal(n.options.open, false);
    assert.equal(summary.focused, true);
});

test('stale generation cannot start the wrong voice and failures allow retry', async () => {
    let resolve;
    const { nodes: n } = setup(() => new Promise(done => { resolve = done; }));
    const pending = n.form.fire('submit');
    n.voice.value = 'male';
    await n.voice.fire('change');
    resolve({ ok: true, json: async () => ({ url: 'http://localhost/audio/female' }) });
    await pending;
    assert.equal(n.audio.playCalls, 0);
    assert.equal(n.load.disabled, false);
    globalThis.fetch = async () => { throw new TypeError('Internal network details'); };
    await n.form.fire('submit');
    assert.equal(n.load.disabled, false);
    assert.equal(n.status.textContent, 'Unavailable');
    assert.equal(n.status.classes.has('sr-only'), false);
});
