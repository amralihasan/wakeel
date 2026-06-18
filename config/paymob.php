<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paymob API Keys
    |--------------------------------------------------------------------------
    |
    | The Paymob API key is used to authenticate requests to the Paymob API.
    |
    */

    'api_key' => env('PAYMOB_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Paymob HMAC Secret
    |--------------------------------------------------------------------------
    |
    | The HMAC secret is used to securely verify transaction callbacks sent from Paymob.
    |
    */

    'hmac_secret' => env('PAYMOB_HMAC_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Paymob Iframe ID
    |--------------------------------------------------------------------------
    |
    | The default iframe ID used to render the card payment form.
    |
    */

    'iframe_id' => env('PAYMOB_IFRAME_ID'),

    /*
    |--------------------------------------------------------------------------
    | Paymob Integrations
    |--------------------------------------------------------------------------
    |
    | Define the Integration IDs for different payment methods.
    |
    */

    'integrations' => [
        'card' => env('PAYMOB_CARD_INTEGRATION_ID'),
        'wallet' => env('PAYMOB_WALLET_INTEGRATION_ID'),
    ],
];
