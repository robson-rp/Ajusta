<?php

namespace App\Domains\Billing\Gateways\AppyPay;

use App\Domains\Billing\Application\ChargeService;
use App\Domains\Billing\Models\BillingCharge;

/**
 * Applies an AppyPay status notification to the matching charge.
 */
class AppyPayWebhookHandler
{
    public function __construct(private readonly ChargeService $charges) {}

    public function isValidToken(?string $token): bool
    {
        $expected = (string) config('services.appypay.webhook_token');

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    /** Returns the charge it updated, or null when none matches. */
    public function handle(array $payload): ?BillingCharge
    {
        $merchantId = $payload['merchantTransactionId'] ?? null;

        $charge = is_string($merchantId)
            ? BillingCharge::where('merchant_transaction_id', $merchantId)->first()
            : null;

        if ($charge === null && isset($payload['id'])) {
            $charge = BillingCharge::where('provider_id', $payload['id'])->first();
        }

        if ($charge === null) {
            return null;
        }

        return $this->charges->transition($charge, self::statusFrom($payload), $payload);
    }

    /**
     * successful=true → paid; otherwise cancelled / expired / failed.
     */
    public static function statusFrom(array $payload): string
    {
        $status = $payload['responseStatus'] ?? [];

        if (($status['successful'] ?? false) === true) {
            return BillingCharge::PAID;
        }

        return match (strtolower((string) ($status['status'] ?? ''))) {
            'cancelled', 'canceled' => BillingCharge::CANCELLED,
            'expired' => BillingCharge::EXPIRED,
            'pending', 'processing', '' => BillingCharge::PENDING,
            default => BillingCharge::FAILED,
        };
    }
}
