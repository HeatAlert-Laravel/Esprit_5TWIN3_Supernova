# Module 3 — Heat Distress voice assistant

The public `/cooling-points` page offers a microphone, an editable text alternative, optional GPS, and French, Arabic or Tunisian Derja responses. It accepts a recording of up to 30 seconds / 4 MiB, transcribes it with Groq Whisper, and extracts the explicitly spoken street/city and reduced-mobility requirement using structured JSON. A spoken address is resolved using Photon and **always confirmed by the resident**, even if only one match is returned. Confirmed GPS skips address lookup. No guessed/default city coordinates are used by the assistant.

The backend selects the nearest eligible record using Haversine distance, independently of the page's current filters. The model never chooses a destination, invents coordinates, or writes medical/navigation instructions. Fixed localized templates produce two short sentences. Distances are explicitly straight-line estimates, not walking distance or turn-by-turn guidance. An optional Google Maps link opens only after the resident chooses it.

## Enable locally

1. Pull the feature branch and install the existing locked dependencies (`composer install`, `npm ci`). No new packages are added. Follow the project's PHP/Node requirements.
2. Run `php artisan migrate`. The additive migration creates `eau_potable` and `ombre`, both false for existing records. It preserves all existing cooling points.
3. Put a Groq key in the ignored `.env`: `HEAT_ASSISTANT_GROQ_API_KEY=...`. Leaving that variable empty uses the existing `GROQ_API_KEY`. Never commit the real key.
4. Run `php artisan config:clear` and `npm run build`.
5. In **Admin → Cooling points → Edit**, confirm **Drinking water available** and **Shaded resting area available** only for places whose amenities you have checked. Record coordinates, set the point active, and give known daily opening hours. Existing seed descriptions and type names do not count as confirmation; no real or demo records are silently certified by the migration.
6. Open `/cooling-points` over HTTPS or `http://localhost` / `http://127.0.0.1`. Choose a language, press the microphone, speak, and press **Terminer et envoyer** (or wait for the 30-second limit). **Annuler**, Escape and leaving the page stop recording; cancellation discards unsent audio. You can also type without a microphone.

Supported eligibility hours: `24/7`, `24h/24`, `always open`, or a complete **daily** `HH:MM - HH:MM` range, including overnight hours. Unknown, blank, malformed or weekday-specific free text is excluded. End times are exclusive. Hours are evaluated in `Africa/Tunis`, independent of the application's UTC setting. These are published schedules, not live occupancy/availability guarantees.

The default search radius is **5 km**; no eligible point in that radius returns an honest no-match response. The optional wheelchair control filters `accessible_pmr`; a spoken accessibility need is also honored. A pool or misting station is never assumed to provide drinking water or shade.

## Configuration

| Variable | Default / purpose |
| --- | --- |
| `HEAT_ASSISTANT_GROQ_API_KEY` | Optional dedicated key, falling back to `GROQ_API_KEY` |
| `HEAT_ASSISTANT_MODEL` | `openai/gpt-oss-20b`; must support strict structured outputs |
| `HEAT_ASSISTANT_SPEECH_MODEL` | `whisper-large-v3` multilingual transcription |
| `HEAT_ASSISTANT_DAILY_LIMIT` | `100` upstream Groq calls in a rolling 24-hour window, independent of Module 4 |
| `HEAT_ASSISTANT_TIMEZONE` | `Africa/Tunis` |
| `HEAT_ASSISTANT_RADIUS_KM` | `5` |
| `HEAT_ASSISTANT_GEOCODER_URL` | `https://photon.komoot.io/api/`; replace with a compatible private Photon endpoint for production; empty disables spoken-address lookup while GPS still works |

The [Photon public demo](https://github.com/komoot/photon#demo-server) permits reasonable project usage, may throttle/ban extensive use, and offers no availability guarantee. Use it only for a modest academic demonstration. Queries are user-initiated, limited across the application to at most one per second, cached by query for 24 hours, restricted to Tunisia, and return up to three choices. The page displays OpenStreetMap attribution. Do not point this variable at the public Nominatim service: it has a different API and policy. For larger deployments use a private Photon instance and a shared cache/lock store across application instances.

## Accessibility and language behavior

The interface includes explicit labels, keyboard controls, visible focus, a polite status region, focused text results, targets at least 44 CSS pixels, replay/stop controls, and Arabic right-to-left layout. These are accessibility provisions, **not a WCAG conformance certification**. Test with the target residents, screen readers and actual devices before claiming conformance.

Whisper automatically detects the input language; the response-language selector does not force the recording into a particular language. Code-switching, background noise, names and Derja pronunciation can reduce recognition accuracy. The transcript stays editable. Validate recognition with Tunisian speakers before presenting accuracy claims.

Speech playback uses the device's Web Speech voices at a slower rate. It prefers French for French and `ar-TN` for Derja, with another Arabic voice as a fallback. There is no guarantee of a native Derja voice; install a French/Arabic system voice if absent. If playback is unsupported or blocked, the complete text remains available and the interface explains the limitation. The read-aloud checkbox can be turned off for screen-reader users. Nothing records, requests GPS or speaks on page load.

GPS is requested only via its button. Readings worse than 200 m are rejected and readings older than 60 seconds must be refreshed. A selected GPS position is explicitly used instead of any spoken address; it can be cleared. Street matches are approximate origins, and a five-minute encrypted token binds the chosen coordinates, language and accessibility need. Confirmation rechecks the current database and opening time; it cannot recommend a subsequently deactivated point.

## Distress handling and data boundaries

A visible warning is always present. Explicit severe wording (for example confusion, unconsciousness, inability to walk or breathing difficulty) produces fixed advice to ask a nearby person to call local emergency services and stay accompanied, before any provider call where wording is recognized locally. The model can also flag severe distress. It does not diagnose heat stroke, guarantee symptom detection, choose treatment, invent an emergency phone number, or send a distressed resident walking alone. The two-sentence regular reply starts with sitting in shade and asking someone nearby for help if dizzy.

The guidance is based on [CDC heat-related illnesses](https://www.cdc.gov/niosh/heat-stress/about/illnesses.html) and [NHS heat exhaustion/heatstroke](https://www.nhs.uk/conditions/heat-exhaustion-heatstroke/). This is a location/accessibility aid, not emergency dispatch or a medical triage service.

Submitted audio goes through the Laravel server to Groq; the transcript/message goes to Groq for extraction. Only the extracted place query goes to Photon; precise GPS stays in the application request and is not sent to Groq/Photon. The app does not write recordings or conversations to permanent storage. PHP temporarily holds uploaded files for the request; geocoding results are cached and encrypted location tokens are sent to the browser. Third-party data processing still applies; see [Groq data handling](https://console.groq.com/docs/your-data) and the geocoder's policies. Browser speech may use the device's configured speech service. The UI discloses providers before submission.

Both public POST endpoints use Laravel's existing CSRF middleware and per-IP throttles (`10/min` for interpretation/confirmation, `6/min` for transcription), plus a separate shared Groq call budget guarded by a cache lock. Audio files and text are bounded and validated. Missing keys, timeouts, bad provider output and exhausted budgets produce readable failures; upstream error details and keys are not returned. AI output is treated as untrusted structured data; all displayed text uses Blade escaping or `textContent`. The normal cooling-points list remains usable when providers fail.

## Verification

```powershell
php vendor/bin/pest tests/Feature/HeatDistressAssistantTest.php tests/Feature/CoolingPointCrudTest.php
node --test tests/js/*.test.mjs
npm run build
```

Server tests fake providers and cover geospatial ranking, confirmed amenities, opening hours/time zone, accessibility, ambiguous locations and token expiry, inactive changes, malformed input, uploads, silence, emergencies, quotas and provider failures. JavaScript tests cover no requests on load, recording/stop/cancel cleanup, stale responses, denied/imprecise GPS, safe links/text, language changes and playback fallback. Live Groq recognition/Derja quality needs a developer-provided key and recordings from consenting speakers; do not claim it was validated by mocked tests.
