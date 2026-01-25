<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the WhatsApp Cloud API (Meta).
    |
    */

    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    'app_secret' => env('WHATSAPP_APP_SECRET'),

];
