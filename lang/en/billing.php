<?php

return [

    'attributes' => [
        'company_name' => 'company name',
    ],

    'errors' => [
        'subscription_inactive' => 'Your account is read-only until the subscription is paid.',
        'plan_feature' => 'Your current plan does not include this feature.',
        'owner_only' => 'Only the account owner can manage the subscription.',
    ],

    'mail' => [
        'open_billing' => 'Open subscription',

        'welcome' => [
            'subject' => 'Welcome to AJUSTA',
            'line1' => 'Your company :company is ready to invoice.',
            'line2' => 'You are on a free trial of the :plan plan until :date.',
            'button' => 'Open AJUSTA',
        ],

        'trial_ending' => [
            'subject' => 'Your AJUSTA trial is ending',
            'line1' => 'Your free trial ends on :date.',
            'line2' => 'Choose a plan and pay by Multicaixa Express or ATM reference to keep invoicing.',
        ],

        'renewal_due' => [
            'subject' => 'Time to renew your AJUSTA subscription',
            'line1' => 'Your :plan plan is paid until :date.',
            'line2' => 'Renew now to keep your account active without interruption.',
        ],

        'suspended' => [
            'subject' => 'Your AJUSTA account is read-only',
            'line1' => 'We did not receive the payment for your subscription.',
            'line2' => 'You can still see your documents. Pay the subscription to issue new ones.',
        ],

        'payment_received' => [
            'subject' => 'Payment received',
            'line1' => 'We received :amount for :months month(s) of the :plan plan.',
            'line2' => 'Your subscription is active until :date.',
            'line3' => 'Transaction: :id',
        ],

        'reference' => [
            'subject' => 'Payment details for your AJUSTA subscription',
            'line1' => 'Pay the :plan plan (:months month(s)) at any ATM or in your bank app, under "Payments by reference":',
            'entity' => 'Entity: :value',
            'reference' => 'Reference: :value',
            'amount' => 'Amount: :value',
            'valid_until' => 'Valid until: :date',
        ],
    ],

];
