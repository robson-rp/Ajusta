<?php

use App\Domains\Accounts\Models\User;

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
        'scheme' => env('MAILGUN_SCHEME', 'https'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
    ],

    'stripe' => [
        'model' => User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    /*
    | AppyPay Charges API (Multicaixa Express / GPO and ATM reference).
    */
    'appypay' => [
        'token_url' => env('APPYPAY_TOKEN_URL'),
        'client_id' => env('APPYPAY_CLIENT_ID'),
        'client_secret' => env('APPYPAY_CLIENT_SECRET'),
        'resource' => env('APPYPAY_RESOURCE'),
        'base_url' => env('APPYPAY_BASE_URL', 'https://gwy-api-tst.appypay.co.ao'),
        'method_gpo' => env('APPYPAY_METHOD_GPO'),
        'method_reference' => env('APPYPAY_METHOD_REFERENCE'),
        'webhook_token' => env('APPYPAY_WEBHOOK_TOKEN'),
        'reference_valid_days' => 10,
    ],

];
