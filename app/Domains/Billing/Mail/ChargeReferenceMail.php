<?php

namespace App\Domains\Billing\Mail;

use App\Domains\Billing\Models\BillingCharge;

class ChargeReferenceMail extends BillingMail
{
    public function __construct(public BillingCharge $charge) {}

    protected function subjectLine(): string
    {
        return __('billing.mail.reference.subject');
    }

    protected function lines(): array
    {
        return [
            __('billing.mail.reference.line1', ['plan' => $this->charge->plan?->name, 'months' => $this->charge->months]),
            __('billing.mail.reference.entity', ['value' => $this->charge->entity_number]),
            __('billing.mail.reference.reference', ['value' => $this->charge->reference_number]),
            __('billing.mail.reference.amount', ['value' => self::kz($this->charge->amount)]),
            __('billing.mail.reference.valid_until', ['date' => self::date($this->charge->expires_at)]),
        ];
    }
}
