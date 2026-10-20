<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'research_model' => env('OPENAI_RESEARCH_MODEL'),
        'writing_model' => env('OPENAI_WRITING_MODEL'),
        'web_search' => filter_var(env('OPENAI_WEB_SEARCH', true), FILTER_VALIDATE_BOOL),
        'timeout' => (int) env('OPENAI_TIMEOUT', 240),
        'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 8000),
        'max_retries' => (int) env('OPENAI_MAX_RETRIES', 3),
    ],

];
