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

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
        'timeout' => env('OLLAMA_TIMEOUT', 60),
    ],

    'nvidia' => [
        'api_key' => env('NVIDIA_API_KEY'),
        'base_url' => env('NVIDIA_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'model' => env('NVIDIA_MODEL', 'meta/llama-3.1-8b-instruct'),
    ],

    'gitlab' => [
        'token' => env('GITLAB_TOKEN'),
        'webhook_secret' => env('GITLAB_WEBHOOK_SECRET'),
        'base_url' => env('GITLAB_BASE_URL', 'https://gitlab.com/api/v4'),
    ],

    'fireflies' => [
        'api_key' => env('FIREFLIES_API_KEY'),
        'webhook_secret' => env('FIREFLIES_WEBHOOK_SECRET'),
    ],

];
