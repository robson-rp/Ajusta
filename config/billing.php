<?php

/*
|--------------------------------------------------------------------------
| AJUSTA subscriptions
|--------------------------------------------------------------------------
|
| Self-service signup, prepaid plans and payment gateways. Prices are in
| cents of Kwanza, like every other amount in the app. Secrets for the
| gateways live in config/services.php, never here.
|
*/

return [

    'signup_enabled' => env('BILLING_SIGNUP_ENABLED', true),

    'currency' => 'AOA',

    'trial_days' => 14,

    // Days after the paid period (or trial) ends before the company is
    // switched to read-only.
    'grace_days' => 3,

    // Days before the end of a paid period when the renewal notice goes out.
    'notice_days' => 7,

    // Days before the end of the trial when reminders go out.
    'trial_reminder_days' => [3, 0],

    // How many months can be prepaid at once.
    'periods' => [1, 3, 6, 12],

    // Electronic invoicing fee, added per month once AGT e-invoicing exists.
    'e_invoice_fee' => [
        'enabled' => env('BILLING_E_INVOICE_FEE_ENABLED', false),
        'amount' => 500000,
    ],

    // Payment gateways offered at checkout, in display order.
    'gateways' => array_filter([
        env('APPYPAY_CLIENT_ID') ? 'appypay' : null,
    ]),

    // Plans seeded on install. Editable afterwards by the super admin.
    'plans' => [
        [
            'code' => 'start',
            'name' => 'Start',
            'price_monthly' => 1500000,
            'sort' => 1,
            'features' => [
                'max_users' => 1,
                'max_companies' => 1,
                'customer_portal' => false,
                'recurring_invoices' => false,
                'advanced_reports' => false,
            ],
        ],
        [
            'code' => 'business',
            'name' => 'Empresa',
            'price_monthly' => 4500000,
            'sort' => 2,
            'features' => [
                'max_users' => null,
                'max_companies' => null,
                'customer_portal' => true,
                'recurring_invoices' => true,
                'advanced_reports' => true,
            ],
        ],
    ],

];
