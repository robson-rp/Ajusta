<?php

namespace App\Domains\Billing\Mail;

use App\Domains\Billing\Models\Subscription;

class TrialEndingMail extends BillingMail
{
    public function __construct(public Subscription $subscription) {}

    protected function subjectLine(): string
    {
        return __('billing.mail.trial_ending.subject');
    }

    protected function lines(): array
    {
        return [
            __('billing.mail.trial_ending.line1', ['date' => self::date($this->subscription->trial_ends_at)]),
            __('billing.mail.trial_ending.line2'),
        ];
    }
}
