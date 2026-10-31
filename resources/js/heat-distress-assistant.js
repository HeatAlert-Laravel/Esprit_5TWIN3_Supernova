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
        const recordLabel = node('record-label'), session = node('session');
        const sessionTitle = node('session-title'), sessionHint = node('session-hint');
        const clock = node('clock'), timer = node('timer'), progress = node('progress');
        const waveform = node('waveform'), thinking = node('thinking'), signal = node('signal');
        const bars = Array.from(root.querySelectorAll('[data-heat-wave-bar]'));
        const errorPanel = node('error'), errorMessage = node('error-message'), retry = node('retry');
        const supportsRecording = !!(window.isSecureContext && navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
        const synthesis = window.speechSynthesis;
        let phase = 'idle', version = 0, controller = null, stream = null, recorder = null;
        let recordingTimer = null, gps = null, responseLanguage = 'fr', utterance = null;
        let audioContext = null, microphoneSource = null, analyser = null, frame = null, clockInterval = null;
        let retryAction = null;
        const text = (key) => copy[language.value][key];
        const sayStatus = (key) => { status.textContent = text(key); };

        function stopSpeech() {
            if (utterance) utterance.onerror = null;
            synthesis?.cancel();
            utterance = null;
        }
        function stopTracks() {
            stopRecordingFeedback();
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
            clearTimeout(recordingTimer);
            recordingTimer = null;
        }
        function setPhase(next) {
            phase = next;
            root.dataset.phase = next;
            const busy = next !== 'idle';
            form.setAttribute('aria-busy', String(busy));
            [language, message, accessible, locate, clearLocation, node('submit')].forEach((control) => { control.disabled = busy; });
            choices.querySelectorAll('button').forEach((button) => { button.disabled = busy; });
            record.disabled = !supportsRecording || !['idle', 'recording'].includes(next);
            record.setAttribute('aria-pressed', String(next === 'recording'));
            recordLabel.textContent = text(next === 'recording' ? 'stop_recording' : 'record');
            cancel.hidden = !busy;
            session.hidden = !['permission', 'recording', 'transcribing', 'request'].includes(next);
            clock.hidden = waveform.hidden = signal.hidden = progress.hidden = next !== 'recording';
            thinking.hidden = next === 'recording';
            const prefix = ({ permission: 'permission', recording: 'listening', transcribing: 'transcribing', request: 'searching' })[next];
            if (prefix) {
                sessionTitle.textContent = text(`${prefix}_title`);
                sessionHint.textContent = text(`${prefix}_hint`);
            }
        }
        function prepareRecordingFeedback() {
            // Use the existing microphone stream; never connect it to speakers.
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            try {
                audioContext = new AudioContext();
                audioContext.resume().catch(() => {});
            } catch { audioContext = null; }
        }
        function stopRecordingFeedback() {
            clearInterval(clockInterval);
            clockInterval = null;
            if (frame !== null) window.cancelAnimationFrame(frame);
            frame = null;
            try { microphoneSource?.disconnect(); } catch { /* A disconnected meter needs no further action. */ }
            microphoneSource = analyser = null;
            if (audioContext) {
                try { audioContext.close().catch(() => {}); } catch { /* Recording cleanup must still complete. */ }
                audioContext = null;
            }
            bars.forEach((bar) => bar.style.setProperty('--voice-level', '.07'));
        }
        function startRecordingFeedback(current) {
            const startedAt = Date.now();
            const levels = bars.map(() => .07);
            let lastSample = 0, lastSound = startedAt, heardSound = false;
            timer.textContent = '00:00';
            progress.value = 0;
            progress.setAttribute('aria-label', text('recording_progress'));
            signal.textContent = text('signal_waiting');
            const updateClock = () => {
                const elapsed = Math.min(30, Math.max(0, (Date.now() - startedAt) / 1000));
                timer.textContent = `00:${String(Math.floor(elapsed)).padStart(2, '0')}`;
                progress.value = elapsed;
            };
            clockInterval = setInterval(updateClock, 250);
            if (!audioContext || !window.requestAnimationFrame) {
                signal.textContent = text('signal_unavailable');
                return;
            }
            try {
                analyser = audioContext.createAnalyser();
                analyser.fftSize = 512;
                microphoneSource = audioContext.createMediaStreamSource(stream);
                microphoneSource.connect(analyser);
                const samples = new Uint8Array(analyser.fftSize);
                const draw = () => {
                    if (current !== version || phase !== 'recording') return;
                    const now = Date.now();
                    if (now - lastSample >= 90) {
                        try { analyser.getByteTimeDomainData(samples); }
                        catch { signal.textContent = text('signal_unavailable'); frame = null; return; }
                        let sum = 0;
                        for (const sample of samples) sum += ((sample - 128) / 128) ** 2;
                        const level = Math.min(1, Math.sqrt(sum / samples.length) * 5);
                        levels.shift(); levels.push(Math.max(.07, level));
                        bars.forEach((bar, index) => bar.style.setProperty('--voice-level', levels[index].toFixed(3)));
                        if (level > .1) { lastSound = now; heardSound = true; }
                        signal.textContent = text(now - lastSound > 5000 ? 'signal_quiet'
                            : heardSound && now - lastSound < 800 ? 'signal_detected' : 'signal_waiting');
                        lastSample = now;
                    }
                    frame = window.requestAnimationFrame(draw);
                };
                frame = window.requestAnimationFrame(draw);
            } catch { signal.textContent = text('signal_unavailable'); }
        }
        function clearResult() {
            stopSpeech();
            clearError();
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
            clearError();
            setPhase('idle');
            if (announce) sayStatus('cancelled');
        }
        function clearError() {
            errorPanel.hidden = true;
            errorMessage.textContent = '';
            retryAction = null;
        }
        function showError(error, stage = 'search', token = null) {
            if (error.name === 'AbortError') return;
            setPhase('idle');
            const expiredLocation = stage === 'search' && token && error.status === 422;
            const key = (expiredLocation ? 'expired' : null) || ({ 419: 'session', 429: 'limit', 422: 'invalid' })[error.status]
                || ({ no_speech: 'no_speech', assistant_not_configured: 'not_configured', location_unavailable: 'location_unavailable' })[error.code]
                || (stage === 'transcription' ? 'transcription_error' : 'search_error');
            errorMessage.textContent = text(error.code === 'no_speech' ? 'no_speech' : key);
            retry.textContent = text(stage === 'transcription' ? 'retry_recording' : 'retry_search');
            retryAction = stage === 'transcription' ? () => beginRecording() : () => getAnswer(expiredLocation ? null : token);
            errorPanel.hidden = false;
            status.textContent = '';
            errorPanel.focus();
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
                if (!response.ok) throw Object.assign(new Error('Assistant request failed'), { status: response.status, code: payload.code });
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
            const target = ({ en: 'en-US', fr: 'fr-FR', derja: 'ar-TN' })[responseLanguage] || 'ar';
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
            node('result-title').textContent = text(payload.status === 'confirm_location' ? 'confirm_location_title'
                : payload.status === 'found' ? 'found_title' : 'result');
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
                if (current === version && payload) {
                    if (typeof payload.text !== 'string' || typeof payload.status !== 'string') throw new Error('Invalid search response');
                    render(payload);
                }
            } catch (error) { if (current === version) showError(error, 'search', token); }
            finally { if (current === version) setPhase('idle'); }
        }
        async function beginRecording() {
            const current = ++version;
            clearResult();
            setPhase('permission');
            sayStatus('permission');
            prepareRecordingFeedback();
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
                        if (typeof payload.text !== 'string' || !payload.text.trim()) throw new Error('Invalid transcription response');
                        message.value = payload.text;
                        await getAnswer(null, current);
                    } catch (error) { if (current === version) showError(error, 'transcription'); }
                    finally { if (current === version) setPhase('idle'); }
                };
                recorder.start();
                setPhase('recording'); sayStatus('recording');
                startRecordingFeedback(current);
                recordingTimer = setTimeout(() => { if (recording.state === 'recording') recording.stop(); }, 30000);
            } catch {
                stopTracks();
                if (current === version) { setPhase('idle'); sayStatus('mic_error'); }
            }
        }
        function translate() {
            root.lang = language.value === 'derja' ? 'ar-TN' : language.value;
            root.dir = ['fr', 'en'].includes(language.value) ? 'ltr' : 'rtl';
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
        retry.addEventListener('click', () => { if (phase === 'idle') retryAction?.(); });
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
