<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bakong Open API Token
    |--------------------------------------------------------------------------
    |
    | Bearer token generated from the NBC Bakong Developer Portal.
    | Required to execute MD5 transaction lookups and account checks.
    |
    */
    'token' => env('BAKONG_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Bakong Open API Endpoint
    |--------------------------------------------------------------------------
    |
    | Production: https://api-bakong.nbc.gov.kh
    | UAT / Sandbox: https://sit-api-bakong.nbc.gov.kh
    |
    */
    'api_url' => env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh'),

    /*
    |--------------------------------------------------------------------------
    | Personal Wallet Account ID
    |--------------------------------------------------------------------------
    |
    | Your Bakong wallet identifier (e.g., name@bkrt, name@wing).
    |
    */
    'account_id' => env('BAKONG_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Display Metadata
    |--------------------------------------------------------------------------
    |
    | Sender's banking app shows these details during scan confirmation.
    |
    */
    'merchant_name' => env('BAKONG_MERCHANT_NAME', env('APP_NAME', 'POS System')),
    'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),

    /*
    |--------------------------------------------------------------------------
    | Currency Configurations
    |--------------------------------------------------------------------------
    |
    | ISO 4217 Currency Codes:
    | USD => 840
    | KHR => 116
    |
    */
    'currency' => [
        'default' => env('BAKONG_CURRENCY', 'USD'),
        'codes'   => [
            'USD' => 840,
            'KHR' => 116,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Polling & Expiration
    |--------------------------------------------------------------------------
    |
    | Dynamic QR validity window in seconds (e.g., 300 = 5 minutes).
    |
    */
    'qr_lifetime_seconds' => env('BAKONG_QR_LIFETIME', 300),

];
