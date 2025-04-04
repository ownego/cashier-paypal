<?php

return [

    'sandbox' => env('PAYPAL_SANDBOX', true),
    'client_id' => env('PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
    'app_id' => env('PAYPAL_APP_ID', 'APP-80W284485P519543T'),
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),

    'brand_name' => env('CASHIER_BRAND_NAME', 'Laravel'),
    'currency' => env('CASHIER_CURRENCY', 'USD'),
    'locale' => env('CASHIER_LOCALE', 'en-US'),

    'path' => env('CASHIER_PATH', 'cashier'),

];
