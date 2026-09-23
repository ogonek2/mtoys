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

    'novaposhta' => [
        'api_key' => env('NOVA_POSHTA_API_KEY', ''),
        'verify_ssl' => filter_var(env('NOVA_POSHTA_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        'base_url' => env('NOVA_POSHTA_API_URL', 'https://api.novaposhta.ua/v2.0/json/'),
    ],

    'telegram' => [
        'bot_token' => env('TG_BOT_TOKEN', ''),
        'chat_id' => env('TG_CHAT_ID', ''),
        // На shared-хостинге SSL к api.telegram.org часто падает.
        'verify_ssl' => filter_var(env('TG_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
