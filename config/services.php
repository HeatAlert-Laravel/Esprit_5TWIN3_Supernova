<?php

return [
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-3.8-flash-lite-tts'),
        'tts_daily_limit' => (int) env('GEMINI_TTS_DAILY_LIMIT', 30),
    ],
    // Module 1 "Explain this alert": its own key and model, independent from the Gemini voice settings above.
    'google' => [
        'key' => env('GOOGLE_API_KEY'),
        'explain_model' => env('GOOGLE_EXPLAIN_MODEL', 'gemini-3.5-flash'),
        'explain_daily_limit' => (int) env('GOOGLE_EXPLAIN_DAILY_LIMIT', 60),
    ],
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'assistant_model' => env('GROQ_ASSISTANT_MODEL', 'openai/gpt-oss-20b'),
        'assistant_fallback_models' => array_values(array_filter(array_map('trim', explode(',', env('GROQ_ASSISTANT_FALLBACK_MODELS', 'openai/gpt-oss-120b'))))),
        'assistant_daily_limit' => (int) env('GROQ_ASSISTANT_DAILY_LIMIT', 100),
    ],
    // Module 5 "household risk advisor": its own key and model, independent from the Groq/Gemini features.
    'mistral' => [
        'key' => env('MISTRAL_API_KEY'),
        // Light, fast model available on the demo key; supports Mistral custom structured outputs.
        'model' => env('MISTRAL_MODEL', 'ministral-8b-latest'),
        'timeout' => (int) env('MISTRAL_TIMEOUT', 15),
        'connect_timeout' => (int) env('MISTRAL_CONNECT_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
