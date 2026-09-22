<?php

return [

    'token' => env('BAKONG_TOKEN'),

    'api_url' => env(
        'BAKONG_API_URL',
        'https://api-bakong.nbc.gov.kh'
    ),

    'account_id' => env('BAKONG_ACCOUNT_ID'),

    'merchant_name' => env(
        'BAKONG_MERCHANT_NAME',
        'POS System'
    ),

    'merchant_city' => env(
        'BAKONG_MERCHANT_CITY',
        'Phnom Penh'
    ),

    'acquiring_bank' => env(
        'BAKONG_ACQUIRING_BANK',
        'Bakong'
    ),

    'merchant_id' => env(
        'BAKONG_MERCHANT_ID',
        '000000000'
    ),

];
