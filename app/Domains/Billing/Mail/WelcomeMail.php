<?php

namespace App\Domains\Billing\Mail;

use App\Domains\Accounts\Models\Company;
use App\Domains\Billing\Models\Subscription;

class WelcomeMail extends BillingMail
{
    public function __construct(public Subscription $subscription, public Company $company) {}

    protected function subjectLine(): string
    {
        return __('billing.mail.welcome.subject');
    }

    protected function lines(): array
    {
        return [
            __('billing.mail.welcome.line1', ['company' => $this->company->name]),
            __('billing.mail.welcome.line2', ['plan' => $this->subscription->plan?->name, 'date' => self::date($this->subscription->trial_ends_at)]),
        ];
    }

    protected function buttonLabel(): ?string
    {
        return __('billing.mail.welcome.button');
    }

    protected function buttonUrl(): ?string
    {
        return url('/admin/dashboard');
    }
}
