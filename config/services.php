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

    'media_api' => [
        'rate_limit_per_minute' => (int) env('MEDIA_API_RATE_LIMIT', 60),
        'default_page_size' => (int) env('MEDIA_API_PAGE_SIZE', 20),
        'maximum_page_size' => (int) env('MEDIA_API_MAX_PAGE_SIZE', 100),
    ],

    'automation' => [
        'model' => env('AUTOMATION_OPENAI_MODEL', env('OPENAI_WRITING_MODEL', 'gpt-5.4')),
        'default_timezone' => env('AUTOMATION_DEFAULT_TIMEZONE', 'America/Argentina/Buenos_Aires'),
        'overlap_minutes' => (int) env('AUTOMATION_RESEARCH_OVERLAP_MINUTES', 15),
        'late_tolerance_minutes' => (int) env('AUTOMATION_LATE_TOLERANCE_MINUTES', 180),
        'quote_valid_days' => (int) env('AUTOMATION_QUOTE_VALID_DAYS', 7),
        'interpret_rate_limit' => (int) env('AUTOMATION_INTERPRET_RATE_LIMIT', 5),
        'run_now_rate_limit' => (int) env('AUTOMATION_RUN_NOW_RATE_LIMIT', 5),
    ],

];
