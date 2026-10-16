export function initAdviceReaders() {
    document.querySelectorAll('[data-advice-reader]').forEach((reader) => {
        const find = (name) => reader.querySelector('[data-reader-' + name + ']');
        const form = find('form'), voice = find('voice'), speed = find('speed');
        const button = find('load'), status = find('status'), audio = find('audio');
        const stop = find('stop'), time = find('time'), options = find('options');
        const seek = find('seek'), seekField = find('seek-field');
        const cached = new Map();
        let controller, requestId = 0;
        button.disabled = false;
        const message = (text, visible = false) => {
            status.textContent = text;
            status.classList.toggle('sr-only', !visible);
        };
        const playing = (active) => {
            find('play-icon').hidden = active;
            find('pause-icon').hidden = !active;
            const label = active ? reader.dataset.pauseLabel : reader.dataset.listenLabel;
            button.setAttribute('aria-label', label);
            button.title = label;
        };
        const stamp = (seconds) => Math.floor(seconds / 60) + ':' + String(Math.floor(seconds % 60)).padStart(2, '0');
        const updateTime = () => {
            if (!Number.isFinite(audio.duration)) return;
            time.textContent = stamp(audio.currentTime) + ' / ' + stamp(audio.duration);
            time.hidden = false;
            seek.max = Math.floor(audio.duration);
            seek.value = Math.floor(audio.currentTime);
            seek.setAttribute('aria-valuetext', stamp(audio.currentTime) + ' / ' + stamp(audio.duration));
            seekField.hidden = false;
        };
        const clear = () => {
            audio.pause();
            audio.removeAttribute('src');
            audio.load();
            playing(false);
            stop.hidden = true;
            time.hidden = true;
            seekField.hidden = true;
        };
        const display = (url) => {
            audio.src = url;
            audio.playbackRate = Number(speed.value);
            stop.hidden = false;
            message(reader.dataset.ready);
        };
        const play = () => audio.play().catch(() => {
            playing(false);
            message(reader.dataset.ready, true);
        });
        voice.addEventListener('change', () => {
            requestId++;
            controller?.abort();
            clear();
            form.action = voice.value;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            message('');
            if (cached.has(voice.value)) display(cached.get(voice.value));
        });
        speed.addEventListener('change', () => { audio.playbackRate = Number(speed.value); });
        seek.addEventListener('input', () => {
            if (Number.isFinite(audio.duration)) audio.currentTime = Number(seek.value);
            updateTime();
        });
        stop.addEventListener('click', () => {
            audio.pause();
            audio.currentTime = 0;
            updateTime();
            message(reader.dataset.paused);
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (button.disabled) return;
            options.open = false;
            if (!audio.paused) {
                audio.pause();
                return;
            }
            if (audio.getAttribute('src')) {
                if (audio.ended) audio.currentTime = 0;
                play();
                return;
            }
            if (cached.has(voice.value)) {
                display(cached.get(voice.value));
                play();
                return;
            }
            const selectedVoice = voice.value;
            const id = ++requestId;
            controller = new AbortController();
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            message(reader.dataset.loading, true);
            try {
                const response = await fetch(form.action, {
                    method: 'POST', credentials: 'same-origin', signal: controller.signal,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(response.status === 419 ? reader.dataset.signIn
                        : (response.status === 429 ? (result.message || reader.dataset.busy)
                            : (result.message || reader.dataset.error)));
                }
                const url = new URL(result.url, window.location.href);
                if (url.origin !== window.location.origin) throw new Error(reader.dataset.error);
                if (id !== requestId) return;
                cached.set(selectedVoice, url.href);
                display(url.href);
                play();
            } catch (error) {
                if (id !== requestId || error.name === 'AbortError') return;
                message(error instanceof TypeError ? reader.dataset.error : (error.message || reader.dataset.error), true);
            } finally {
                if (id === requestId) {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                }
            }
        });
        audio.addEventListener('loadedmetadata', updateTime);
        audio.addEventListener('timeupdate', updateTime);
        audio.addEventListener('error', () => {
            playing(false);
            message(reader.dataset.error, true);
            audio.removeAttribute('src');
        });
        audio.addEventListener('play', () => { playing(true); message(reader.dataset.playing); });
        audio.addEventListener('pause', () => { playing(false); message(reader.dataset.paused); });
        audio.addEventListener('ended', () => { playing(false); message(reader.dataset.finished); });
        document.addEventListener('click', (event) => {
            if (!options.contains(event.target)) options.open = false;
        });
        reader.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && options.open) {
                options.open = false;
                options.querySelector('summary').focus();
            }
        });
        window.addEventListener('pagehide', () => {
            requestId++;
            controller?.abort();
            clear();
        });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAdviceReaders);
else initAdviceReaders();
