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
            <button type="button" class="ha-btn ha-btn--primary" data-heat-record aria-pressed="false" aria-describedby="heat-status" data-i18n="record">{{ $copy['record'] }}</button>
            <button type="button" class="ha-btn ha-btn--outline" data-heat-cancel hidden data-i18n="cancel">{{ $copy['cancel'] }}</button>
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
    <div class="ha-heat-assistant__result" data-heat-result hidden tabindex="-1" aria-labelledby="heat-result-title">
        <h3 id="heat-result-title" data-i18n="result">{{ $copy['result'] }}</h3>
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
