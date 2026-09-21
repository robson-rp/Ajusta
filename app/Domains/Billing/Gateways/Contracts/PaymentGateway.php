<?php

namespace App\Domains\Billing\Gateways\Contracts;

use App\Domains\Billing\Gateways\ChargeResult;
use App\Domains\Billing\Models\BillingCharge;

/**
 * A way to collect a subscription payment. New providers implement this and
 * are listed in config('billing.gateways'); checkout offers their methods
 * automatically.
 */
interface PaymentGateway
{
    public function key(): string;

    /**
     * Methods offered at checkout, keyed by method code.
     *
     * @return array<string, array{label: string, requires_phone: bool}>
     */
    public function methods(): array;

    /**
     * Send the charge to the provider. The charge is already persisted with
     * its amount and merchant transaction id; the gateway only reports back.
     *
     * @param  array{phone?: string|null, name?: string|null, email?: string|null}  $customer
     */
    public function createCharge(BillingCharge $charge, array $customer): ChargeResult;
}
