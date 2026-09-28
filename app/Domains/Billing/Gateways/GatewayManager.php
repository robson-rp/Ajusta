<?php

namespace App\Domains\Billing\Gateways;

use App\Domains\Billing\Gateways\AppyPay\AppyPayGateway;
use App\Domains\Billing\Gateways\Contracts\PaymentGateway;
use App\Domains\Billing\Gateways\StrongPay\StrongPayGateway;
use InvalidArgumentException;

/**
 * Resolves the payment gateways enabled in config('billing.gateways').
 * To add a provider: implement PaymentGateway, add it to $classes and list
 * its key in the config.
 */
class GatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    protected array $classes = [
        'strongpay' => StrongPayGateway::class,
        'appypay' => AppyPayGateway::class,
        'manual' => ManualGateway::class,
    ];

    public function get(string $key): PaymentGateway
    {
        $class = $this->classes[$key] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown payment gateway [{$key}].");
        }

        return app($class);
    }

    /**
     * Gateways offered to customers at checkout (manual is admin-only).
     *
     * @return array<int, PaymentGateway>
     */
    public function enabled(): array
    {
        return collect(config('billing.gateways', []))
            ->filter(fn (string $key) => isset($this->classes[$key]))
            ->map(fn (string $key) => $this->get($key))
            ->values()
            ->all();
    }

    /**
     * Checkout options: one entry per gateway method.
     *
     * @return array<int, array{gateway: string, method: string, label: string, requires_phone: bool}>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->enabled() as $gateway) {
            foreach ($gateway->methods() as $method => $meta) {
                $options[] = ['gateway' => $gateway->key(), 'method' => $method] + $meta;
            }
        }

        return $options;
    }

    /** The enabled gateway offering this method, or null. */
    public function forMethod(string $method): ?PaymentGateway
    {
        foreach ($this->enabled() as $gateway) {
            if (array_key_exists($method, $gateway->methods())) {
                return $gateway;
            }
        }

        return null;
    }
}
