@props(['conseil'])
<div class="ha-audio-reader" role="group" aria-label="{{ __('Listen to this article') }}" data-advice-reader
    data-loading="{{ __('Preparing audio…') }}"
    data-ready="{{ __('Your recording is ready. Press play to listen.') }}"
    data-playing="{{ __('Playing article.') }}" data-paused="{{ __('Playback paused.') }}"
    data-finished="{{ __('Finished listening. You can replay or choose another voice.') }}"
    data-listen-label="{{ __('Listen to article') }}" data-pause-label="{{ __('Pause narration') }}"
    data-error="{{ __('Audio is temporarily unavailable. Please try again later.') }}"
    data-sign-in="{{ __('Your session expired. Refresh this page and try again.') }}"
    data-busy="{{ __('Too many audio requests. Please wait a moment and try again.') }}">
    <form novalidate method="POST" action="{{ route('advice.audio.store', ['conseil' => $conseil, 'voice' => 'female']) }}" data-reader-form>
        @csrf
        <button type="submit" class="ha-btn ha-btn--outline ha-reader-icon" disabled data-reader-load
            aria-label="{{ __('Listen to article') }}" title="{{ __('Listen to article') }}">
            <span data-reader-play-icon><x-ha.icon name="play" size="sm" /></span>
            <span data-reader-pause-icon hidden><x-ha.icon name="pause" size="sm" /></span>
        </button>
    </form>
    <span class="ha-reader-time" data-reader-time hidden aria-hidden="true"></span>
    <button type="button" class="ha-btn ha-btn--ghost ha-reader-icon" hidden data-reader-stop
        aria-label="{{ __('Stop narration') }}" title="{{ __('Stop narration') }}"><x-ha.icon name="stop" size="sm" /></button>
    <details class="ha-reader-options" data-reader-options>
        <summary class="ha-btn ha-btn--ghost ha-reader-icon" aria-label="{{ __('Narration settings') }}" title="{{ __('Voice and speed') }}"><x-ha.icon name="sliders" size="sm" /></summary>
        <div class="ha-reader-menu">
            <div class="ha-field"><label for="article-voice" class="ha-label">{{ __('Narrator') }}</label>
                <select id="article-voice" class="ha-select" data-reader-voice>
                    <option value="{{ route('advice.audio.store', ['conseil' => $conseil, 'voice' => 'female']) }}">{{ __('Kore · Female') }}</option>
                    <option value="{{ route('advice.audio.store', ['conseil' => $conseil, 'voice' => 'male']) }}">{{ __('Charon · Male') }}</option>
                </select>
            </div>
            <div class="ha-field"><label for="article-speed" class="ha-label">{{ __('Playback speed') }}</label>
                <select id="article-speed" class="ha-select" data-reader-speed>
                    <option value="0.75">0.75×</option><option value="1" selected>1×</option>
                    <option value="1.25">1.25×</option><option value="1.5">1.5×</option>
                </select>
            </div>
            <div class="ha-field" hidden data-reader-seek-field>
                <label for="article-position" class="ha-label">{{ __('Playback position') }}</label>
                <input id="article-position" type="range" min="0" max="0" step="1" value="0" data-reader-seek>
            </div>
        </div>
    </details>
    <span class="ha-reader-message sr-only" role="status" aria-live="polite" data-reader-status></span>
    <audio preload="none" hidden data-reader-audio></audio>
    <noscript><span class="ha-help">{{ __('Enable JavaScript to use the article reader.') }}</span></noscript>
</div>
