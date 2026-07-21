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

    'catalog' => [
        'base_url' => env('CATALOG_SERVICE_URL', 'http://catalog-service:8000'),
        'api_key' => env('CATALOG_API_KEY'),
        'timeout_ms' => (int) env('CATALOG_HTTP_TIMEOUT_MS', 2000),
        'retry_count' => (int) env('CATALOG_HTTP_RETRY_COUNT', 1),
    ],

    'pagamento' => [
        'prazo_segundos' => (int) env('PAGAMENTO_PRAZO_SEGUNDOS', 30),
        'max_erros_chave' => (int) env('PAGAMENTO_MAX_ERROS_CHAVE', 3),
    ],

];
