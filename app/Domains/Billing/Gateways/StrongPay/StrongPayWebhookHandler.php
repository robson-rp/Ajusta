<?php

namespace App\Domains\Billing\Gateways\StrongPay;

use App\Domains\Billing\Application\ChargeService;
use App\Domains\Billing\Models\BillingCharge;

/**
 * Applies a StrongPay payment.updated notification to the matching charge.
 */
class StrongPayWebhookHandler
{
    public function __construct(private readonly ChargeService $charges) {}

    /**
     * X-StrongPay-Signature is "sha256=" + HMAC-SHA256 of the raw body. A
     * missing secret rejects everything rather than accepting unsigned calls.
     */
    public function isValidSignature(string $body, ?string $signature): bool
    {
        $secret = (string) config('services.strongpay.webhook_secret');

        if ($secret === '' || ! is_string($signature)) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $body, $secret), $signature);
    }

    /** Returns the charge it updated, or null when none matches. */
    public function handle(array $payload): ?BillingCharge
    {
        $paymentId = $payload['payment_id'] ?? null;

        $charge = is_string($paymentId) && $paymentId !== ''
            ? BillingCharge::where('gateway', 'strongpay')->where('provider_id', $paymentId)->first()
            : null;

        if ($charge === null) {
            return null;
        }

        return $this->charges->transition($charge, self::statusFrom($payload), $payload);
    }

    public static function statusFrom(array $payload): string
    {
        return match (strtolower((string) ($payload['status'] ?? ''))) {
            'paid' => BillingCharge::PAID,
            'failed' => BillingCharge::FAILED,
            'cancelled', 'canceled' => BillingCharge::CANCELLED,
            'expired' => BillingCharge::EXPIRED,
            default => BillingCharge::PENDING,
        };
    }
}
