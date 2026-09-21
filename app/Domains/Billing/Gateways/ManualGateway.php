<?php

namespace App\Domains\Billing\Gateways;

use App\Domains\Billing\Gateways\Contracts\PaymentGateway;
use App\Domains\Billing\Models\BillingCharge;

/**
 * Payments confirmed by the platform admin (bank transfer, cash, or any
 * method not integrated yet). Never offered at checkout.
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function methods(): array
    {
        return ['manual' => ['label' => 'billing.methods.manual', 'requires_phone' => false]];
    }

    public function createCharge(BillingCharge $charge, array $customer): ChargeResult
    {
        return new ChargeResult(successful: true, message: 'Recorded by administrator');
    }
}
