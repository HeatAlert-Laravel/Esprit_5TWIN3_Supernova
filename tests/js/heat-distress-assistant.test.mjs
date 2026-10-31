import test from 'node:test';
import assert from 'node:assert/strict';
import { initHeatDistressAssistants } from '../../resources/js/heat-distress-assistant.js';

class Element {
    constructor() { this.listeners = {}; this.attrs = {}; this.dataset = {}; this.children = []; this.hidden = false; this.value = ''; this.textContent = ''; this.checked = false; }
    addEventListener(event, callback) { (this.listeners[event] ||= []).push(callback); }
    emit(event, data = {}) { for (const callback of this.listeners[event] || []) callback({ preventDefault() {}, ...data }); }
    setAttribute(key, value) { this.attrs[key] = value; }
    removeAttribute(key) { delete this.attrs[key]; }
    querySelectorAll() { return this.children; }
    append(element) { this.children.push(element); }
    replaceChildren() { this.children = []; }
    focus() { this.focused = true; }
    reportValidity() { this.validated = true; }
}
const tick = async () => { for (let i = 0; i < 6; i++) await new Promise(setImmediate); };
const response = (body, status = 200) => ({ ok: status === 200, status, json: async () => body });
const found = { status: 'found', language: 'fr', text: 'Sit in shade. A safe registered point is nearby.', origin: 'Your GPS',
    choices: [], point: { latitude: 36.8, longitude: 10.18 }, directions_url: 'javascript:alert(1)' };

function setup({ recording = false, media, fetcher = async () => response(found), geolocation } = {}) {
    const names = ['form', 'language', 'message', 'record', 'cancel', 'status', 'result', 'answer', 'choices', 'locate',
        'clear-location', 'directions', 'origin', 'distance-note', 'accessible', 'read', 'replay', 'stop-audio', 'submit'];
    const n = Object.fromEntries(names.map((name) => [name, new Element()]));
    const keys = ['title', 'ready', 'record', 'stop_recording', 'cancelled', 'unsupported', 'mic_error', 'voice_missing', 'invalid',
        'limit', 'session', 'unavailable', 'location_error', 'located', 'loading', 'transcribing', 'permission', 'recording', 'locating', 'origin', 'placeholder', 'no_speech'];
    const copy = Object.fromEntries(['fr', 'ar', 'derja'].map((lang) => [lang, Object.fromEntries(keys.map((key) => [key, `${lang}:${key}`]))]));
    const root = new Element();
    root.dataset = { copy: JSON.stringify(copy), endpoint: '/cooling-points/assistant', transcribeEndpoint: '/cooling-points/assistant/transcribe' };
    root.querySelector = (selector) => n[selector.slice(11, -1)];
    root.querySelectorAll = () => [];
    n.language.value = 'fr'; n.message.value = 'Je suis à Tunis'; n.read.checked = true;
    n.result.hidden = n.directions.hidden = n['clear-location'].hidden = true;
    const tracks = { stopped: 0, getTracks() { return [{ stop: () => this.stopped++ }]; } };
    const recorders = [];
    class Recorder {
        static isTypeSupported = () => true;
        constructor() { this.mimeType = 'audio/webm'; this.state = 'inactive'; recorders.push(this); }
        start() { this.state = 'recording'; }
        stop() {
            this.state = 'inactive';
            queueMicrotask(() => {
                this.ondataavailable({ data: new Blob(['audio'], { type: this.mimeType }) });
                this.onstop();
            });
        }
    }
    const speech = { spoken: [], cancelled: 0, cancel() { this.cancelled++; }, getVoices: () => [{ lang: 'fr-FR' }, { lang: 'ar-TN' }], speak(utterance) { this.spoken.push(utterance); } };
    const win = new Element();
    Object.assign(win, { isSecureContext: true, speechSynthesis: speech, SpeechSynthesisUtterance: class { constructor(text) { this.text = text; } } });
    if (recording) win.MediaRecorder = Recorder;
    globalThis.window = win;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
        mediaDevices: { getUserMedia: media || (async () => tracks) }, geolocation,
    } });
    globalThis.document = { querySelectorAll: () => [root], querySelector: () => ({ content: 'test-csrf' }), createElement: () => new Element() };
    const requests = [];
    globalThis.fetch = async (url, options) => { requests.push({ url, options }); return fetcher(url, options); };
    initHeatDistressAssistants();
    return { n, root, win, speech, tracks, requests, recorders, submit: async () => { n.form.emit('submit'); await tick(); } };
}

test('initialization never asks for audio or GPS; text works when recording is unsupported', async () => {
    const { n, requests, submit, speech } = setup();
    assert.equal(n.record.disabled, true);
    assert.equal(requests.length, 0);
    assert.equal(speech.spoken.length, 0);
    await submit();
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.equal(n.answer.textContent, found.text);
    assert.equal(n.result.focused, true);
    assert.equal(n.directions.href.startsWith('https://www.google.com/maps/dir/'), true);
    assert.equal(n.directions.href.includes('javascript'), false);
    assert.equal(speech.spoken.length, 1);
    n['stop-audio'].emit('click');
    assert.ok(speech.cancelled > 0);
});

test('stopping a recording sends one multipart transcription and one text request then closes microphone', async () => {
    const { n, requests, tracks, recorders } = setup({ recording: true, fetcher: async (url) =>
        response(url.endsWith('/transcribe') ? { text: 'راني في تونس' } : { ...found, language: 'derja' }) });
    n.language.value = 'derja'; n.language.emit('change');
    n.record.emit('click'); await tick();
    assert.equal(recorders[0].state, 'recording');
    assert.equal(n.record.attrs['aria-pressed'], 'true');
    n.record.emit('click'); await tick();
    assert.equal(tracks.stopped, 1);
    assert.equal(requests.length, 2);
    assert.equal(requests[0].options.body.get('audio').type, 'audio/webm');
    assert.equal(requests[1].options.body.get('message'), 'راني في تونس');
    assert.equal(n.form.attrs['aria-busy'], 'false');
});

test('cancel during delayed permission closes late microphone and sends nothing', async () => {
    let grant;
    const { n, tracks, requests } = setup({ recording: true, media: () => new Promise((resolve) => { grant = resolve; }) });
    n.record.emit('click');
    n.cancel.emit('click');
    grant(tracks); await tick();
    assert.equal(tracks.stopped, 1);
    assert.equal(requests.length, 0);
    assert.equal(n.status.textContent, 'fr:cancelled');
});

test('Escape cancels recording and late responses cannot replace the result', async () => {
    const recording = setup({ recording: true });
    recording.n.record.emit('click'); await tick();
    recording.root.emit('keydown', { key: 'Escape' }); await tick();
    assert.equal(recording.tracks.stopped, 1);
    assert.equal(recording.requests.length, 0);
    let resolve;
    const active = setup({ fetcher: () => new Promise((done) => { resolve = done; }) });
    active.n.form.emit('submit');
    active.n.cancel.emit('click');
    resolve(response(found)); await tick();
    assert.equal(active.n.result.hidden, true);
    assert.equal(active.n.status.textContent, 'fr:cancelled');
});

test('spoken address choices render as text and confirmation posts only the opaque location token', async () => {
    const { n, submit, requests } = setup({ fetcher: async () => response({ status: 'confirm_location', language: 'fr', text: 'Choose location.', choices: [{ label: '<img src=x onerror=alert(1)>', token: 'opaque' }] }) });
    await submit();
    assert.equal(n.choices.children[0].textContent, '<img src=x onerror=alert(1)>');
    n.choices.children[0].emit('click'); await tick();
    assert.equal(requests[1].options.body.get('location_token'), 'opaque');
    assert.equal(requests[1].options.body.has('message'), false);
    n.message.emit('input');
    assert.equal(n.result.hidden, true);
    assert.equal(n.choices.children.length, 0);
});

test('denied and inaccurate GPS never manufacture coordinates and RTL selection updates language', async () => {
    const { n, submit, requests, root } = setup({ geolocation: { getCurrentPosition(success) { success({ coords: { latitude: 36.8, longitude: 10.18, accuracy: 900 } }); } } });
    n.locate.emit('click');
    assert.equal(n.status.textContent, 'fr:location_error');
    await submit();
    assert.equal(requests[0].options.body.has('latitude'), false);
    n.language.value = 'derja'; n.language.emit('change');
    assert.equal(root.lang, 'ar-TN');
    assert.equal(root.dir, 'rtl');
    assert.equal(n.result.hidden, true);
});

test('GPS is opt-in can be cleared and does not send precise coordinates to the speech provider', async () => {
    const { n, submit, requests } = setup({ geolocation: { getCurrentPosition(success) { success({ coords: { latitude: 36.8, longitude: 10.18, accuracy: 10 } }); } } });
    n.locate.emit('click'); await submit();
    assert.equal(requests[0].options.body.get('latitude'), '36.8');
    n['clear-location'].emit('click'); await submit();
    assert.equal(requests[1].options.body.has('latitude'), false);
});

test('provider failures restore controls and missing voices leave the text usable', async () => {
    for (const [status, key] of [[419, 'session'], [429, 'limit'], [422, 'invalid'], [503, 'unavailable']]) {
        const { n, submit } = setup({ fetcher: async () => response({}, status) });
        await submit();
        assert.equal(n.status.textContent, `fr:${key}`);
        assert.equal(n.submit.disabled, false);
    }
    const { n, submit, speech } = setup();
    speech.getVoices = () => [];
    await submit();
    assert.equal(n.answer.textContent, found.text);
    assert.equal(n.status.textContent, 'fr:voice_missing');
});
