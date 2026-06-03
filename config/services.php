<?php

return [

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        // Stronger GPT-4-class model used only for the reasoning-heavy steps
        // (query planning + Text-to-SQL generation) where accuracy matters most.
        // Falls back to the default model when OPENAI_SQL_MODEL is not set.
        'sql_model' => env('OPENAI_SQL_MODEL', 'gpt-4.1'),
        'max_output_tokens' => env('OPENAI_MAX_OUTPUT_TOKENS', 1200),
        'timeout' => env('OPENAI_TIMEOUT', 60),
    ],

];
