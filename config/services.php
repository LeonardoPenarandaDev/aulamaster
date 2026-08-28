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

    /*
     * Fase 17 del checklist: pasarela de pagos en línea. Construida con
     * variables de entorno vacías a propósito — no hay credenciales reales
     * todavía, así que el flujo no se puede probar de punta a punta hasta
     * que se completen en el .env (llaves de sandbox de Wompi).
     */
    'wompi' => [
        'env' => env('WOMPI_ENV', 'sandbox'),
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET'),
        'events_secret' => env('WOMPI_EVENTS_SECRET'),
        'sandbox_url' => 'https://sandbox.wompi.co/v1',
        'production_url' => 'https://production.wompi.co/v1',
        'checkout_url' => 'https://checkout.wompi.co/p/',
    ],

];
