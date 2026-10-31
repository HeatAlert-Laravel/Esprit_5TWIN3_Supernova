@php
    $assistantCopy = collect(['fr', 'ar', 'derja'])->mapWithKeys(fn ($language) => [$language => \Illuminate\Support\Facades\Lang::get('heat_assistant', [], $language)]);
    $copy = $assistantCopy['fr'];
@endphp
<section class="ha-heat-assistant" data-heat-assistant lang="fr" dir="ltr" aria-labelledby="heat-assistant-title"
    data-endpoint="{{ route('cooling-points.assistant') }}"
    data-transcribe-endpoint="{{ route('cooling-points.assistant.transcribe') }}"
    data-copy="{{ json_encode($assistantCopy, JSON_UNESCAPED_UNICODE) }}">
    <div class="ha-heat-assistant__heading">
        <span class="ha-heat-assistant__symbol" aria-hidden="true">🎙️</span>
        <div>
            <h2 id="heat-assistant-title" data-i18n="title">{{ $copy['title'] }}</h2>
            <p data-i18n="intro">{{ $copy['intro'] }}</p>
        </div>
    </div>
    <p class="ha-heat-assistant__safety" data-i18n="safety">{{ $copy['safety'] }}</p>
    <form data-heat-form>
        <div class="ha-heat-assistant__toolbar">
            <div>
                <label for="heat-language" data-i18n="language">{{ $copy['language'] }}</label>
                <select id="heat-language" class="ha-select" data-heat-language>
                    <option value="fr" lang="fr">Français</option>
                    <option value="ar" lang="ar">العربية</option>
                    <option value="derja" lang="ar-TN">تونسي · Derja</option>
                </select>
            </div>
            <button type="button" class="ha-btn ha-btn--primary ha-heat-assistant__record" data-heat-record aria-pressed="false" aria-describedby="heat-status">
                <svg class="ha-heat-assistant__mic-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3M8 22h8"/></svg>
                <span class="ha-heat-assistant__stop-icon" aria-hidden="true"></span>
                <span data-heat-record-label data-i18n="record">{{ $copy['record'] }}</span>
            </button>
            <button type="button" class="ha-btn ha-btn--outline" data-heat-cancel hidden data-i18n="cancel">{{ $copy['cancel'] }}</button>
        </div>
        <div class="ha-voice-session" data-heat-session hidden>
            <div class="ha-voice-session__top">
                <div class="ha-voice-session__state">
                    <span class="ha-voice-session__indicator" aria-hidden="true"></span>
                    <strong data-heat-session-title>{{ $copy['listening_title'] }}</strong>
                </div>
                <span class="ha-voice-session__clock" data-heat-clock aria-hidden="true"><time data-heat-timer>00:00</time><span> / 00:30</span></span>
            </div>
            <div class="ha-voice-session__visual" data-heat-waveform aria-hidden="true">
                <span class="ha-voice-session__mic">
                    <svg viewBox="0 0 24 24"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3M8 22h8"/></svg>
                </span>
                <div class="ha-voice-session__wave">
                    @for($bar = 0; $bar < 32; $bar++)<span data-heat-wave-bar></span>@endfor
                </div>
            </div>
            <div class="ha-voice-session__thinking" data-heat-thinking hidden aria-hidden="true"><span></span><span></span><span></span></div>
            <p class="ha-voice-session__hint" data-heat-session-hint>{{ $copy['listening_hint'] }}</p>
            <p class="ha-voice-session__signal" data-heat-signal aria-hidden="true">{{ $copy['signal_waiting'] }}</p>
            <progress class="ha-voice-session__progress" data-heat-progress max="30" value="0" aria-label="{{ $copy['recording_progress'] }}" aria-live="off"></progress>
        </div>
        <label for="heat-message" data-i18n="message">{{ $copy['message'] }}</label>
        <textarea id="heat-message" class="ha-input" rows="2" required maxlength="1000" data-heat-message dir="auto"
            placeholder="{{ $copy['placeholder'] }}" aria-describedby="heat-privacy"></textarea>
        <div class="ha-heat-assistant__toolbar">
            <button type="button" class="ha-btn ha-btn--outline" data-heat-locate data-i18n="locate">{{ $copy['locate'] }}</button>
            <button type="button" class="ha-btn ha-btn--outline" data-heat-clear-location hidden data-i18n="clear_location">{{ $copy['clear_location'] }}</button>
            <label><input type="checkbox" data-heat-accessible> <span data-i18n="accessible">{{ $copy['accessible'] }}</span></label>
            <label><input type="checkbox" data-heat-read checked> <span data-i18n="read_aloud">{{ $copy['read_aloud'] }}</span></label>
        </div>
        <button type="submit" class="ha-btn ha-btn--primary" data-heat-submit data-i18n="submit">{{ $copy['submit'] }}</button>
    </form>
    <p id="heat-status" class="ha-heat-assistant__status" role="status" aria-live="polite" aria-atomic="true" data-heat-status>{{ $copy['ready'] }}</p>
    <div class="ha-heat-assistant__error" data-heat-error hidden tabindex="-1" role="alert" aria-labelledby="heat-error-title">
        <h3 id="heat-error-title" data-i18n="error_title">{{ $copy['error_title'] }}</h3>
        <p data-heat-error-message></p>
        <button type="button" class="ha-btn ha-btn--outline" data-heat-retry>{{ $copy['retry_search'] }}</button>
    </div>
    <div class="ha-heat-assistant__result" data-heat-result hidden tabindex="-1" aria-labelledby="heat-result-title">
        <h3 id="heat-result-title" data-heat-result-title data-i18n="result">{{ $copy['result'] }}</h3>
        <p data-heat-answer dir="auto"></p>
        <p data-heat-origin hidden dir="auto"></p>
        <div class="ha-heat-assistant__choices" data-heat-choices></div>
        <div class="ha-heat-assistant__toolbar">
            <button type="button" class="ha-btn ha-btn--outline" data-heat-replay data-i18n="replay">{{ $copy['replay'] }}</button>
            <button type="button" class="ha-btn ha-btn--outline" data-heat-stop-audio data-i18n="stop_audio">{{ $copy['stop_audio'] }}</button>
            <a class="ha-btn ha-btn--primary" data-heat-directions hidden target="_blank" rel="noopener noreferrer" data-i18n="directions">{{ $copy['directions'] }}</a>
        </div>
        <p data-heat-distance-note hidden data-i18n="approximate">{{ $copy['approximate'] }}</p>
    </div>
    <p id="heat-privacy" class="ha-heat-assistant__privacy" data-i18n="privacy">{{ $copy['privacy'] }}</p>
    <p class="ha-heat-assistant__privacy">Geocoding: <a href="https://photon.komoot.io" target="_blank" rel="noopener noreferrer">Photon</a> · © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a></p>
    <noscript><p>{{ $copy['no_js'] }}</p></noscript>
</section>
