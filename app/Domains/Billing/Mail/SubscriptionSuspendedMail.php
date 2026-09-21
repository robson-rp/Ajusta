<?php

namespace App\Domains\Billing\Mail;

use App\Domains\Billing\Models\Subscription;

class SubscriptionSuspendedMail extends BillingMail
{
    public function __construct(public Subscription $subscription) {}

    protected function subjectLine(): string
    {
        return __('billing.mail.suspended.subject');
    }

    protected function lines(): array
    {
        return [
            __('billing.mail.suspended.line1'),
            __('billing.mail.suspended.line2'),
        ];
    }
}
