// Audio and GPS are requested only by explicit buttons, never on page load.
export function initHeatDistressAssistants() {
    document.querySelectorAll('[data-heat-assistant]').forEach((root) => {
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';
        const node = (name) => root.querySelector(`[data-heat-${name}]`);
        const copy = JSON.parse(root.dataset.copy);
        const form = node('form'), language = node('language'), message = node('message');
        const record = node('record'), cancel = node('cancel'), status = node('status');
        const result = node('result'), answer = node('answer'), choices = node('choices');
        const locate = node('locate'), clearLocation = node('clear-location');
        const directions = node('directions'), origin = node('origin'), distanceNote = node('distance-note');
        const accessible = node('accessible'), read = node('read');
        const replay = node('replay'), stopAudio = node('stop-audio');
        const supportsRecording = !!(window.isSecureContext && navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
        const synthesis = window.speechSynthesis;
        let phase = 'idle', version = 0, controller = null, stream = null, recorder = null;
        let recordingTimer = null, gps = null, responseLanguage = 'fr', utterance = null;
        const text = (key) => copy[language.value][key];
        const sayStatus = (key) => { status.textContent = text(key); };

        function stopSpeech() {
            if (utterance) utterance.onerror = null;
            synthesis?.cancel();
            utterance = null;
        }
        function stopTracks() {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
            clearTimeout(recordingTimer);
            recordingTimer = null;
        }
        function setPhase(next) {
            phase = next;
            const busy = next !== 'idle';
            form.setAttribute('aria-busy', String(busy));
            [language, message, accessible, locate, clearLocation, node('submit')].forEach((control) => { control.disabled = busy; });
            choices.querySelectorAll('button').forEach((button) => { button.disabled = busy; });
            record.disabled = !supportsRecording || !['idle', 'recording'].includes(next);
            record.setAttribute('aria-pressed', String(next === 'recording'));
            record.textContent = text(next === 'recording' ? 'stop_recording' : 'record');
            cancel.hidden = !busy;
        }
        function clearResult() {
            stopSpeech();
            result.hidden = true;
            answer.textContent = '';
            choices.replaceChildren();
            directions.hidden = true;
            directions.removeAttribute('href');
            origin.hidden = true;
            distanceNote.hidden = true;
        }
        function cancelOperation(announce = true) {
            version++;
            controller?.abort();
            controller = null;
            if (recorder && recorder.state !== 'inactive') recorder.stop();
            recorder = null;
            stopTracks();
            stopSpeech();
            setPhase('idle');
            if (announce) sayStatus('cancelled');
        }
        function showError(error) {
            if (error.name === 'AbortError') return;
            const key = ({ 419: 'session', 429: 'limit', 422: 'invalid' })[error.status] || 'unavailable';
            sayStatus(key);
        }
        async function post(url, data, current) {
            const requestController = new AbortController();
            controller = requestController;
            const timer = setTimeout(() => requestController.abort(), 45000);
            try {
                const response = await fetch(url, {
                    method: 'POST', body: data, credentials: 'same-origin', signal: requestController.signal,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });
                if (current !== version) return null;
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw Object.assign(new Error('Assistant request failed'), { status: response.status });
                return payload;
            } catch (error) {
                if (current === version && error.name === 'AbortError') throw new Error('Request timed out');
                throw error;
            } finally {
                clearTimeout(timer);
                if (controller === requestController) controller = null;
            }
        }
        function speak() {
            stopSpeech();
            if (!answer.textContent) return;
            if (!synthesis || !window.SpeechSynthesisUtterance) { sayStatus('voice_missing'); return; }
            const target = responseLanguage === 'fr' ? 'fr-FR' : responseLanguage === 'derja' ? 'ar-TN' : 'ar';
            const voices = synthesis.getVoices();
            const voice = voices.find((voice) => voice.lang.toLowerCase() === target.toLowerCase())
                || voices.find((voice) => voice.lang.toLowerCase().startsWith(target.slice(0, 2)));
            if (!voice) { sayStatus('voice_missing'); return; }
            utterance = new window.SpeechSynthesisUtterance(answer.textContent);
            utterance.voice = voice;
            utterance.lang = voice.lang;
            utterance.rate = 0.85;
            utterance.onerror = () => sayStatus('voice_missing');
            synthesis.speak(utterance);
        }
        function render(payload) {
            answer.textContent = payload.text;
            responseLanguage = payload.language;
            result.hidden = false;
            choices.replaceChildren();
            for (const choice of payload.choices || []) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ha-btn ha-btn--outline';
                button.dir = 'auto';
                button.textContent = choice.label;
                button.addEventListener('click', () => getAnswer(choice.token));
                choices.append(button);
            }
            if (payload.status === 'found' && payload.point) {
                // Build the link from numeric coordinates, never a provider-generated URL.
                const { latitude, longitude } = payload.point;
                if (Number.isFinite(latitude) && Math.abs(latitude) <= 90 && Number.isFinite(longitude) && Math.abs(longitude) <= 180) {
                    directions.href = `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}&travelmode=walking`;
                    directions.hidden = false;
                }
                origin.textContent = `${text('origin')}: ${payload.origin}`;
                origin.hidden = false;
                distanceNote.hidden = false;
            }
            status.textContent = '';
            result.focus();
            if (read.checked) speak();
        }
        async function getAnswer(token = null, current = ++version) {
            if (phase !== 'idle' && phase !== 'transcribing') return;
            if (!token && !message.value.trim()) { message.reportValidity(); return; }
            clearResult();
            setPhase('request');
            sayStatus('loading');
            const data = new FormData();
            data.set('language', language.value);
            if (token) data.set('location_token', token);
            else {
                data.set('message', message.value.trim());
                data.set('accessible', accessible.checked ? '1' : '0');
                if (gps && Date.now() - gps.timestamp <= 60000) {
                    data.set('latitude', String(gps.latitude));
                    data.set('longitude', String(gps.longitude));
                    data.set('accuracy', String(gps.accuracy));
                } else if (gps) {
                    // Stale coordinates must not silently become a spoken-address search.
                    gps = null;
                    clearLocation.hidden = true;
                    setPhase('idle');
                    sayStatus('location_error');
                    return;
                }
            }
            try {
                const payload = await post(root.dataset.endpoint, data, current);
                if (current === version && payload) render(payload);
            } catch (error) { if (current === version) showError(error); }
            finally { if (current === version) setPhase('idle'); }
        }
        async function beginRecording() {
            const current = ++version;
            clearResult();
            setPhase('permission');
            sayStatus('permission');
            try {
                const granted = await navigator.mediaDevices.getUserMedia({ audio: true });
                if (current !== version) { granted.getTracks().forEach((track) => track.stop()); return; }
                stream = granted;
                const mimeType = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus']
                    .find((type) => window.MediaRecorder.isTypeSupported(type));
                recorder = new window.MediaRecorder(stream, mimeType ? { mimeType } : undefined);
                const recording = recorder;
                const chunks = [];
                recorder.ondataavailable = (event) => { if (event.data.size) chunks.push(event.data); };
                recorder.onerror = () => { if (current === version) { cancelOperation(false); sayStatus('mic_error'); } };
                recorder.onstop = async () => {
                    if (current !== version) return;
                    stopTracks();
                    recorder = null;
                    const type = recording.mimeType || chunks[0]?.type || 'audio/webm';
                    const blob = new Blob(chunks, { type });
                    if (!blob.size || blob.size > 4 * 1024 * 1024) {
                        setPhase('idle'); sayStatus('no_speech'); return;
                    }
                    setPhase('transcribing'); sayStatus('transcribing');
                    const data = new FormData();
                    const extension = type.includes('mp4') ? 'mp4' : type.includes('ogg') ? 'ogg' : 'webm';
                    data.set('audio', blob, `recording.${extension}`);
                    data.set('language', language.value);
                    try {
                        const payload = await post(root.dataset.transcribeEndpoint, data, current);
                        if (current !== version || !payload) return;
                        message.value = payload.text;
                        await getAnswer(null, current);
                    } catch (error) { if (current === version) showError(error); }
                    finally { if (current === version) setPhase('idle'); }
                };
                recorder.start();
                setPhase('recording'); sayStatus('recording');
                recordingTimer = setTimeout(() => { if (recording.state === 'recording') recording.stop(); }, 30000);
            } catch {
                stopTracks();
                if (current === version) { setPhase('idle'); sayStatus('mic_error'); }
            }
        }
        function translate() {
            root.lang = language.value === 'derja' ? 'ar-TN' : language.value;
            root.dir = language.value === 'fr' ? 'ltr' : 'rtl';
            root.querySelectorAll('[data-i18n]').forEach((element) => { element.textContent = text(element.dataset.i18n); });
            message.placeholder = text('placeholder');
            sayStatus(supportsRecording ? 'ready' : 'unsupported');
            setPhase('idle');
        }
        record.addEventListener('click', () => {
            if (phase === 'recording' && recorder?.state === 'recording') recorder.stop();
            else if (phase === 'idle') beginRecording();
        });
        cancel.addEventListener('click', () => cancelOperation());
        form.addEventListener('submit', (event) => { event.preventDefault(); if (phase === 'idle') getAnswer(); });
        language.addEventListener('change', () => { cancelOperation(false); clearResult(); translate(); });
        [message, accessible].forEach((control) => control.addEventListener('input', clearResult));
        read.addEventListener('change', () => { if (!read.checked) stopSpeech(); });
        replay.addEventListener('click', speak);
        stopAudio.addEventListener('click', stopSpeech);
        locate.addEventListener('click', () => {
            clearResult();
            if (!navigator.geolocation) { sayStatus('location_error'); return; }
            const current = ++version;
            setPhase('locating'); sayStatus('locating');
            navigator.geolocation.getCurrentPosition((position) => {
                if (current !== version) return;
                setPhase('idle');
                if (!Number.isFinite(position.coords.accuracy) || position.coords.accuracy > 200) {
                    gps = null; clearLocation.hidden = true; sayStatus('location_error'); return;
                }
                gps = { latitude: position.coords.latitude, longitude: position.coords.longitude, accuracy: position.coords.accuracy, timestamp: Date.now() };
                clearLocation.hidden = false;
                sayStatus('located');
            }, () => { if (current === version) { gps = null; clearLocation.hidden = true; setPhase('idle'); sayStatus('location_error'); } },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        });
        clearLocation.addEventListener('click', () => { gps = null; clearLocation.hidden = true; clearResult(); sayStatus('ready'); });
        root.addEventListener('keydown', (event) => { if (event.key === 'Escape') { cancelOperation(); record.focus(); } });
        window.addEventListener('pagehide', () => cancelOperation(false));
        // Warm the asynchronous browser voice list without starting playback.
        synthesis?.getVoices();
        translate();
    });
}
