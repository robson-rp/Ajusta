<?php

namespace App\Domains\Billing\Gateways;

use Carbon\CarbonInterface;

/**
 * What a gateway reports right after a charge is created.
 */
final class ChargeResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $providerId = null,
        public readonly ?int $code = null,
        public readonly ?string $message = null,
        public readonly ?string $referenceNumber = null,
        public readonly ?string $entityNumber = null,
        public readonly ?CarbonInterface $expiresAt = null,
    ) {}

    public static function failed(?string $message, ?int $code = null): self
    {
        return new self(successful: false, code: $code, message: $message);
    }
}
