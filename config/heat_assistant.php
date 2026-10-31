<?php

return [
    // A member-specific key can override the application's existing Groq key.
    'key' => env('HEAT_ASSISTANT_GROQ_API_KEY') ?: env('GROQ_API_KEY'),
    'model' => env('HEAT_ASSISTANT_MODEL', 'openai/gpt-oss-20b'),
    'speech_model' => env('HEAT_ASSISTANT_SPEECH_MODEL', 'whisper-large-v3'),
    'daily_limit' => (int) env('HEAT_ASSISTANT_DAILY_LIMIT', 100),
    'timezone' => env('HEAT_ASSISTANT_TIMEZONE', 'Africa/Tunis'),
    'radius_km' => (float) env('HEAT_ASSISTANT_RADIUS_KM', 5),
    // Photon demo: modest, user-initiated academic use only; replace for production.
    'geocoder_url' => env('HEAT_ASSISTANT_GEOCODER_URL', 'https://photon.komoot.io/api/'),
];
