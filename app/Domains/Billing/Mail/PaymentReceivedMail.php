<?php

namespace App\Domains\Billing\Mail;

use App\Domains\Billing\Models\BillingCharge;

class PaymentReceivedMail extends BillingMail
{
    public function __construct(public BillingCharge $charge) {}

    protected function subjectLine(): string
    {
        return __('billing.mail.payment_received.subject');
    }

    protected function lines(): array
    {
        return [
            __('billing.mail.payment_received.line1', ['amount' => self::kz($this->charge->amount), 'plan' => $this->charge->plan?->name, 'months' => $this->charge->months]),
            __('billing.mail.payment_received.line2', ['date' => self::date($this->charge->subscription?->current_period_ends_at)]),
            __('billing.mail.payment_received.line3', ['id' => $this->charge->merchant_transaction_id]),
        ];
    }
}
