import test from 'node:test';
import assert from 'node:assert/strict';
import { initHeatDistressAssistants } from '../../resources/js/heat-distress-assistant.js';

class Element {
    constructor() { this.listeners = {}; this.attrs = {}; this.dataset = {}; this.children = []; this.hidden = false; this.value = ''; this.textContent = ''; this.checked = false; this.styleValues = {}; this.style = { setProperty: (key, value) => { this.styleValues[key] = value; } }; }
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

function setup({ recording = false, media, fetcher = async () => response(found), geolocation, metering = false } = {}) {
    const names = ['form', 'language', 'message', 'record', 'cancel', 'status', 'result', 'answer', 'choices', 'locate',
        'clear-location', 'directions', 'origin', 'distance-note', 'accessible', 'read', 'replay', 'stop-audio', 'submit',
        'record-label', 'session', 'session-title', 'session-hint', 'clock', 'timer', 'progress', 'waveform', 'thinking', 'signal',
        'error', 'error-message', 'retry', 'result-title'];
    const n = Object.fromEntries(names.map((name) => [name, new Element()]));
    const keys = ['title', 'ready', 'record', 'stop_recording', 'cancelled', 'unsupported', 'mic_error', 'voice_missing', 'invalid',
        'limit', 'session', 'unavailable', 'location_error', 'located', 'loading', 'transcribing', 'permission', 'recording', 'locating', 'origin', 'placeholder', 'no_speech',
        'listening_title', 'listening_hint', 'permission_title', 'permission_hint', 'transcribing_title', 'transcribing_hint',
        'searching_title', 'searching_hint', 'signal_waiting', 'signal_detected', 'signal_quiet', 'signal_unavailable', 'recording_progress',
        'error_title', 'search_error', 'transcription_error', 'retry_search', 'retry_recording', 'not_configured', 'location_unavailable',
        'confirm_location_title', 'found_title', 'result', 'expired'];
    const copy = Object.fromEntries(['fr', 'ar', 'derja', 'en'].map((lang) => [lang, Object.fromEntries(keys.map((key) => [key, `${lang}:${key}`]))]));
    const root = new Element();
    root.dataset = { copy: JSON.stringify(copy), endpoint: '/cooling-points/assistant', transcribeEndpoint: '/cooling-points/assistant/transcribe' };
    root.querySelector = (selector) => n[selector.slice(11, -1)];
    const bars = Array.from({ length: 32 }, () => new Element());
    root.querySelectorAll = (selector) => selector === '[data-heat-wave-bar]' ? bars : [];
    n.language.value = 'fr'; n.message.value = 'Je suis à Tunis'; n.read.checked = true;
    n.result.hidden = n.directions.hidden = n['clear-location'].hidden = n.error.hidden = true;
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
    const speech = { spoken: [], cancelled: 0, cancel() { this.cancelled++; }, getVoices: () => [{ lang: 'fr-FR' }, { lang: 'ar-TN' }, { lang: 'en-US' }], speak(utterance) { this.spoken.push(utterance); } };
    const win = new Element();
    Object.assign(win, { isSecureContext: true, speechSynthesis: speech, SpeechSynthesisUtterance: class { constructor(text) { this.text = text; } } });
    if (recording) win.MediaRecorder = Recorder;
    const meter = { level: 128, contexts: 0, closed: 0, disconnected: 0, frames: new Map(), nextFrame: 0 };
    if (metering) {
        win.AudioContext = class {
            constructor() { meter.contexts++; }
            resume() { return Promise.resolve(); }
            close() { meter.closed++; return Promise.resolve(); }
            createAnalyser() { return { fftSize: 512, getByteTimeDomainData: (samples) => samples.fill(meter.level) }; }
            createMediaStreamSource() { return { connect() {}, disconnect() { meter.disconnected++; } }; }
        };
        win.requestAnimationFrame = (callback) => { const id = ++meter.nextFrame; meter.frames.set(id, callback); return id; };
        win.cancelAnimationFrame = (id) => meter.frames.delete(id);
    }
    globalThis.window = win;
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
        mediaDevices: { getUserMedia: media || (async () => tracks) }, geolocation,
    } });
    globalThis.document = { querySelectorAll: () => [root], querySelector: () => ({ content: 'test-csrf' }), createElement: () => new Element() };
    const requests = [];
    globalThis.fetch = async (url, options) => { requests.push({ url, options }); return fetcher(url, options); };
    initHeatDistressAssistants();
    return { n, root, win, speech, tracks, requests, recorders, bars, meter,
        draw: () => { const callbacks = [...meter.frames.values()]; meter.frames.clear(); callbacks.forEach((callback) => callback()); },
        submit: async () => { n.form.emit('submit'); await tick(); } };
}

test('initialization never asks for audio or GPS; text works when recording is unsupported', async () => {
    const { n, requests, submit, speech } = setup();
    assert.equal(n.record.disabled, true);
    assert.equal(requests.length, 0);
    assert.equal(n.session.hidden, true);
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
    assert.equal(n.session.hidden, false);
    assert.equal(n['session-title'].textContent, 'derja:listening_title');
    assert.equal(n.timer.textContent, '00:00');
    assert.equal(n.waveform.hidden, false);
    n.record.emit('click'); await tick();
    assert.equal(tracks.stopped, 1);
    assert.equal(requests.length, 2);
    assert.equal(requests[0].options.body.get('audio').type, 'audio/webm');
    assert.equal(requests[1].options.body.get('message'), 'راني في تونس');
    assert.equal(n.form.attrs['aria-busy'], 'false');
    assert.equal(n.session.hidden, true);
});

test('live sound levels react to audio and the analyser closes on cancellation', async () => {
    const { n, root, meter, bars, draw, requests, tracks } = setup({ recording: true, metering: true });
    assert.equal(meter.contexts, 0);
    n.record.emit('click'); await tick();
    assert.equal(root.dataset.phase, 'recording');
    assert.equal(meter.contexts, 1);
    meter.level = 180;
    draw();
    assert.ok(Number(bars.at(-1).styleValues['--voice-level']) > .5);
    assert.equal(n.signal.textContent, 'fr:signal_detected');
    n.cancel.emit('click'); await tick();
    assert.equal(root.dataset.phase, 'idle');
    assert.equal(meter.closed, 1);
    assert.equal(meter.disconnected, 1);
    assert.equal(meter.frames.size, 0);
    assert.equal(tracks.stopped, 1);
    assert.equal(requests.length, 0);
    assert.equal(n.session.hidden, true);
});

test('missing audio analyser keeps recording usable and visibly identifies the limitation', async () => {
    const { n, requests } = setup({ recording: true });
    n.record.emit('click'); await tick();
    assert.equal(n.signal.textContent, 'fr:signal_unavailable');
    assert.equal(n.record.disabled, false);
    assert.equal(n.timer.textContent, '00:00');
    n.cancel.emit('click'); await tick();
    assert.equal(requests.length, 0);
});

test('elapsed time progresses and extended silence prompts the resident without interrupting recording', async (context) => {
    let now = 100000, updateClock;
    context.mock.method(Date, 'now', () => now);
    context.mock.method(globalThis, 'setInterval', (callback) => { updateClock = callback; return 1; });
    context.mock.method(globalThis, 'clearInterval', () => {});
    const { n, meter, draw, requests } = setup({ recording: true, metering: true });
    n.record.emit('click'); await tick();
    now += 7250;
    updateClock(); draw();
    assert.equal(n.timer.textContent, '00:07');
    assert.equal(n.progress.value, 7.25);
    assert.equal(n.signal.textContent, 'fr:signal_quiet');
    assert.equal(n.record.attrs['aria-pressed'], 'true');
    meter.level = 180;
    now += 100; draw();
    assert.equal(n.signal.textContent, 'fr:signal_detected');
    n.cancel.emit('click'); await tick();
    assert.equal(requests.length, 0);
});

test('denied microphone access closes its visualisation context and restores idle controls', async () => {
    const { n, root, meter, requests } = setup({ recording: true, metering: true, media: async () => { throw new Error('Permission denied'); } });
    n.record.emit('click'); await tick();
    assert.equal(meter.closed, 1);
    assert.equal(root.dataset.phase, 'idle');
    assert.equal(n.session.hidden, true);
    assert.equal(n.record.disabled, false);
    assert.equal(n.status.textContent, 'fr:mic_error');
    assert.equal(requests.length, 0);
});

test('processing states stay visible after sending and disappear when the result is ready', async () => {
    let transcriptionReady, answerReady;
    const { n, root, meter } = setup({ recording: true, metering: true, fetcher: (url) => new Promise((resolve) => {
        if (url.endsWith('/transcribe')) transcriptionReady = resolve; else answerReady = resolve;
    }) });
    n.record.emit('click'); await tick();
    n.record.emit('click'); await tick();
    assert.equal(root.dataset.phase, 'transcribing');
    assert.equal(n['session-title'].textContent, 'fr:transcribing_title');
    assert.equal(n.waveform.hidden, true);
    assert.equal(n.thinking.hidden, false);
    assert.equal(meter.closed, 1);
    transcriptionReady(response({ text: 'Je suis à Tunis' })); await tick();
    assert.equal(root.dataset.phase, 'request');
    assert.equal(n['session-title'].textContent, 'fr:searching_title');
    answerReady(response(found)); await tick();
    assert.equal(n.session.hidden, true);
    assert.equal(n.result.hidden, false);
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
    assert.equal(n['result-title'].textContent, 'fr:confirm_location_title');
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

test('English selection preserves the message and speaks English results with an installed English voice', async () => {
    const englishText = 'If you feel dizzy, sit in the shade and ask someone nearby for help. The nearest listed point has drinking water and shade.';
    const { n, root, speech, requests, submit } = setup({ fetcher: async () => response({ ...found, language: 'en', text: englishText }) });
    const englishVoice = { name: 'Microsoft David', lang: 'en-US' };
    speech.getVoices = () => [englishVoice];
    n.message.value = 'I am on Habib Bourguiba Avenue in Tunis. Where can I find drinking water and shade?';
    n.language.value = 'en';
    n.language.emit('change');
    assert.equal(root.lang, 'en');
    assert.equal(root.dir, 'ltr');
    assert.equal(n.message.value, 'I am on Habib Bourguiba Avenue in Tunis. Where can I find drinking water and shade?');
    assert.equal(requests.length, 0);
    assert.equal(speech.spoken.length, 0);
    await submit();
    assert.equal(requests[0].options.body.get('language'), 'en');
    assert.equal(n.answer.textContent, englishText);
    assert.equal(speech.spoken.length, 1);
    assert.equal(speech.spoken[0].voice, englishVoice);
    assert.equal(speech.spoken[0].lang, 'en-US');
    assert.equal(speech.spoken[0].text, englishText);
    assert.notEqual(n.status.textContent, 'en:voice_missing');
});

test('GPS is opt-in can be cleared and does not send precise coordinates to the speech provider', async () => {
    const { n, submit, requests } = setup({ geolocation: { getCurrentPosition(success) { success({ coords: { latitude: 36.8, longitude: 10.18, accuracy: 10 } }); } } });
    n.locate.emit('click'); await submit();
    assert.equal(requests[0].options.body.get('latitude'), '36.8');
    n['clear-location'].emit('click'); await submit();
    assert.equal(requests[1].options.body.has('latitude'), false);
});

test('provider failures show a focused error card and restore controls while missing voices keep text usable', async () => {
    for (const [status, key] of [[419, 'session'], [429, 'limit'], [422, 'invalid'], [503, 'search_error']]) {
        const { n, submit } = setup({ fetcher: async () => response({}, status) });
        await submit();
        assert.equal(n['error-message'].textContent, `fr:${key}`);
        assert.equal(n.error.hidden, false);
        assert.equal(n.error.focused, true);
        assert.equal(n.submit.disabled, false);
    }
    const { n, submit, speech } = setup();
    speech.getVoices = () => [];
    await submit();
    assert.equal(n.answer.textContent, found.text);
    assert.equal(n.status.textContent, 'fr:voice_missing');
});

test('a successful transcript followed by search failure offers retry without another recording', async () => {
    let searchAttempts = 0;
    const { n, requests } = setup({ recording: true, fetcher: async (url) => {
        if (url.endsWith('/transcribe')) return response({ text: 'Je suis avenue Habib Bourguiba à Tunis.' });
        return ++searchAttempts === 1 ? response({ code: 'interpretation_unavailable', message: 'private upstream details' }, 503) : response(found);
    } });
    n.record.emit('click'); await tick();
    n.record.emit('click'); await tick();
    assert.equal(n.message.value, 'Je suis avenue Habib Bourguiba à Tunis.');
    assert.equal(n.error.hidden, false);
    assert.equal(n['error-message'].textContent, 'fr:search_error');
    assert.equal(n.retry.textContent, 'fr:retry_search');
    assert.equal(n.error.focused, true);
    assert.equal(n.session.hidden, true);
    n.retry.emit('click'); await tick();
    assert.equal(n.error.hidden, true);
    assert.equal(n.result.hidden, false);
    assert.equal(requests.filter(({ url }) => url.endsWith('/transcribe')).length, 1);
    assert.equal(requests.length, 3);
    assert.equal(requests[2].options.body.get('message'), n.message.value);
});

test('address lookup errors explain using GPS and never display provider error text', async () => {
    const { n, submit } = setup({ fetcher: async () => response({ code: 'location_unavailable', message: '<script>private error</script>' }, 503) });
    await submit();
    assert.equal(n['error-message'].textContent, 'fr:location_unavailable');
    n.message.emit('input');
    assert.equal(n.error.hidden, true);
});

test('an expired address confirmation retries from the preserved transcript with fresh choices', async () => {
    let attempts = 0;
    const { n, submit, requests } = setup({ fetcher: async () => ++attempts === 2 ? response({}, 422)
        : response({ status: 'confirm_location', language: 'fr', text: 'Choose a location.', choices: [{ label: 'Tunis', token: 'opaque' }] }) });
    await submit();
    n.choices.children[0].emit('click'); await tick();
    assert.equal(n['error-message'].textContent, 'fr:expired');
    n.retry.emit('click'); await tick();
    assert.equal(requests.length, 3);
    assert.equal(requests[2].options.body.has('location_token'), false);
    assert.equal(requests[2].options.body.get('message'), n.message.value);
    assert.equal(n.choices.children.length, 1);
});

test('transcription failures offer an explicit new recording and never search with stale text', async () => {
    const { n, requests } = setup({ recording: true, fetcher: async () => response({ code: 'no_speech' }, 422) });
    n.record.emit('click'); await tick();
    n.record.emit('click'); await tick();
    assert.equal(n.error.hidden, false);
    assert.equal(n['error-message'].textContent, 'fr:no_speech');
    assert.equal(n.retry.textContent, 'fr:retry_recording');
    assert.equal(requests.length, 1);
    n.retry.emit('click'); await tick();
    assert.equal(n.record.attrs['aria-pressed'], 'true');
    assert.equal(n.error.hidden, true);
    n.cancel.emit('click'); await tick();
});

test('malformed successful search replies also produce a visible retry instead of an empty result', async () => {
    const { n, submit } = setup({ fetcher: async () => response({}) });
    await submit();
    assert.equal(n.error.hidden, false);
    assert.equal(n['error-message'].textContent, 'fr:search_error');
    assert.equal(n.result.hidden, true);
});
