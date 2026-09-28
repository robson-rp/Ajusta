<?php

namespace App\Domains\Billing\Gateways\StrongPay;

use App\Domains\Billing\Gateways\ChargeResult;
use App\Domains\Billing\Gateways\Contracts\PaymentGateway;
use App\Domains\Billing\Models\BillingCharge;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * StrongPay payments API (a layer over AppyPay). Charges are created
 * synchronously; the final status arrives through the payment.updated
 * webhook StrongPay sends to /api/webhooks/strongpay.
 */
class StrongPayGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'strongpay';
    }

    public function methods(): array
    {
        $methods = [];

        if (config('billing.gpo_enabled')) {
            $methods['gpo'] = ['label' => 'billing.methods.gpo', 'requires_phone' => true];
        }

        $methods['reference'] = ['label' => 'billing.methods.reference', 'requires_phone' => false];

        return $methods;
    }

    public function createCharge(BillingCharge $charge, array $customer): ChargeResult
    {
        $config = config('services.strongpay');

        $payload = array_filter([
            'method' => $charge->method === 'gpo' ? 'gpo' : 'ref',
            'amount' => $charge->amountInKwanza(),
            'currency' => 'AOA',
            'description' => $this->description($charge),
            'product' => 'ajusta',
            'customer_name' => $customer['name'] ?? null,
            'customer_email' => $customer['email'] ?? null,
            'customer_phone' => $charge->method === 'gpo' && ! empty($customer['phone'])
                ? '+244'.$customer['phone']
                : null,
        ], fn ($value) => $value !== null);

        try {
            $response = Http::baseUrl(rtrim((string) $config['base_url'], '/'))
                ->acceptJson()
                ->asJson()
                ->timeout(45)
                ->connectTimeout(10)
                ->withToken((string) $config['api_key'])
                ->post('/api/v1/payments', $payload);
        } catch (ConnectionException $e) {
            Log::warning('StrongPay charge failed to send', ['charge' => $charge->uuid, 'error' => $e->getMessage()]);

            return ChargeResult::failed('billing.errors.gateway_unavailable');
        }

        if (! $response->successful() || $response->json('provider_successful') === false) {
            $message = $response->json('provider_message') ?? $this->detailMessage($response->json('detail'));

            Log::info('StrongPay charge refused', [
                'charge' => $charge->uuid,
                'http' => $response->status(),
                'code' => $response->json('provider_code'),
                'message' => $message,
            ]);

            return ChargeResult::failed($message ?? 'billing.errors.charge_refused', $this->code($response->json('provider_code')) ?? $response->status());
        }

        $expiresAt = $response->json('expires_at');

        return new ChargeResult(
            successful: true,
            providerId: $response->json('id'),
            code: $this->code($response->json('provider_code')),
            message: $response->json('provider_message'),
            referenceNumber: $response->json('payment_reference'),
            entityNumber: $response->json('entity_number'),
            expiresAt: $expiresAt ? Carbon::parse($expiresAt) : null,
        );
    }

    /** Short plain text: StrongPay prefixes the system name and AppyPay rejects special characters. */
    private function description(BillingCharge $charge): string
    {
        $plan = Str::studly(Str::ascii((string) $charge->plan?->name));

        return mb_substr(trim('Ajusta '.$plan.' '.$charge->months.'m'), 0, 30);
    }

    private function code(mixed $code): ?int
    {
        return is_numeric($code) ? (int) $code : null;
    }

    private function detailMessage(mixed $detail): ?string
    {
        if (is_string($detail)) {
            return $detail;
        }

        if (is_array($detail) && isset($detail[0]['msg'])) {
            return (string) $detail[0]['msg'];
        }

        return null;
    }
}
