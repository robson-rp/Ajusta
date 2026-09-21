<?php

namespace App\Domains\Billing\Mail;

use App\Domains\Billing\Models\Subscription;

class RenewalDueMail extends BillingMail
{
    public function __construct(public Subscription $subscription) {}

    protected function subjectLine(): string
    {
        return __('billing.mail.renewal_due.subject');
    }

    protected function lines(): array
    {
        return [
            __('billing.mail.renewal_due.line1', ['plan' => $this->subscription->plan?->name, 'date' => self::date($this->subscription->current_period_ends_at)]),
            __('billing.mail.renewal_due.line2'),
        ];
    }
}
