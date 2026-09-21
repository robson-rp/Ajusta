<?php

namespace App\Domains\Billing\Gateways\AppyPay;

use App\Domains\Billing\Gateways\ChargeResult;
use App\Domains\Billing\Gateways\Contracts\PaymentGateway;
use App\Domains\Billing\Models\BillingCharge;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AppyPay Charges API: Multicaixa Express (GPO) and ATM reference.
 * Charges are created synchronously; the final status of a GPO or
 * reference payment arrives later through the webhook.
 */
class AppyPayGateway implements PaymentGateway
{
    public function __construct(private readonly AppyPayTokenProvider $tokens) {}

    public function key(): string
    {
        return 'appypay';
    }

    public function methods(): array
    {
        $config = config('services.appypay');
        $methods = [];

        if (! empty($config['method_gpo'])) {
            $methods['gpo'] = ['label' => 'billing.methods.gpo', 'requires_phone' => true];
        }

        if (! empty($config['method_reference'])) {
            $methods['reference'] = ['label' => 'billing.methods.reference', 'requires_phone' => false];
        }

        return $methods;
    }

    public function createCharge(BillingCharge $charge, array $customer): ChargeResult
    {
        $config = config('services.appypay');

        $payload = [
            'amount' => $charge->amountInKwanza(),
            'currency' => 'AOA',
            'description' => mb_substr('AJUSTA '.$charge->plan?->name.' '.$charge->months.'m', 0, 50),
            'merchantTransactionId' => $charge->merchant_transaction_id,
            'paymentMethod' => $charge->method === 'gpo' ? $config['method_gpo'] : $config['method_reference'],
            'notify' => array_filter([
                'name' => $customer['name'] ?? null,
                'telephone' => $customer['phone'] ?? null,
                'email' => $customer['email'] ?? null,
                'smsNotification' => false,
                'emailNotification' => false,
            ], fn ($value) => $value !== null),
        ];

        if ($charge->method === 'gpo') {
            $payload['paymentInfo'] = ['phoneNumber' => $customer['phone'] ?? null];
        }

        try {
            $response = $this->send($payload);
        } catch (ConnectionException|RuntimeException $e) {
            Log::warning('AppyPay charge failed to send', ['charge' => $charge->uuid, 'error' => $e->getMessage()]);

            return ChargeResult::failed('billing.errors.gateway_unavailable');
        }

        $status = $response->json('responseStatus') ?? [];

        if (! $response->successful() || ! ($status['successful'] ?? false)) {
            Log::info('AppyPay charge refused', [
                'charge' => $charge->uuid,
                'http' => $response->status(),
                'code' => $status['code'] ?? null,
                'message' => $status['message'] ?? null,
            ]);

            return ChargeResult::failed($status['message'] ?? 'billing.errors.charge_refused', $status['code'] ?? $response->status());
        }

        $reference = $status['reference'] ?? $response->json('reference') ?? [];

        return new ChargeResult(
            successful: true,
            providerId: $response->json('id'),
            code: $status['code'] ?? null,
            message: $status['message'] ?? null,
            referenceNumber: $reference['referenceNumber'] ?? null,
            entityNumber: $reference['entity'] ?? null,
            expiresAt: isset($reference['dueDate'])
                ? Carbon::parse($reference['dueDate'])
                : ($charge->method === 'reference' ? now()->addDays((int) $config['reference_valid_days']) : null),
        );
    }

    /**
     * POST /v2.0/charges, renewing the token and retrying once on 401.
     */
    private function send(array $payload): Response
    {
        $request = fn () => Http::baseUrl(rtrim((string) config('services.appypay.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->connectTimeout(10)
            ->withToken($this->tokens->token())
            ->post('/v2.0/charges', $payload);

        $response = $request();

        if ($response->status() === 401) {
            $this->tokens->forget();
            $response = $request();
        }

        return $response;
    }
}
