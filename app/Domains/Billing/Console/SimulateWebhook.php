<?php

namespace App\Domains\Billing\Console;

use App\Domains\Billing\Gateways\AppyPay\AppyPayWebhookHandler;
use App\Domains\Billing\Gateways\StrongPay\StrongPayWebhookHandler;
use App\Domains\Billing\Models\BillingCharge;
use Illuminate\Console\Command;

/**
 * Feeds a fake gateway notification through the real webhook handler of the
 * charge's gateway, for local testing without a public HTTPS URL. Refuses to
 * run in production.
 */
class SimulateWebhook extends Command
{
    protected $signature = 'billing:simulate-webhook {charge : Charge uuid or merchant transaction id} {status=paid : paid|failed|cancelled|expired}';

    protected $description = 'Simulate a payment gateway webhook for a charge (local testing only)';

    public function handle(StrongPayWebhookHandler $strongPay, AppyPayWebhookHandler $appyPay): int
    {
        if (app()->environment('production')) {
            $this->error('Not available in production.');

            return self::FAILURE;
        }

        $key = (string) $this->argument('charge');
        $charge = BillingCharge::where('uuid', $key)->orWhere('merchant_transaction_id', $key)->first();

        if ($charge === null) {
            $this->error('Charge not found.');

            return self::FAILURE;
        }

        $status = (string) $this->argument('status');

        if ($charge->gateway === 'strongpay') {
            $strongPay->handle([
                'event' => 'payment.updated',
                'payment_id' => $charge->provider_id,
                'status' => $status,
                'amount' => number_format($charge->amountInKwanza(), 2, '.', ''),
                'currency' => $charge->currency,
                'payment_reference' => $charge->reference_number,
                'entity_number' => $charge->entity_number,
                'paid_at' => $status === 'paid' ? now()->toIso8601String() : null,
                'updated_at' => now()->toIso8601String(),
                'simulated' => true,
            ]);
        } else {
            $appyPay->handle([
                'id' => $charge->provider_id ?? 'simulated',
                'merchantTransactionId' => $charge->merchant_transaction_id,
                'amount' => $charge->amountInKwanza(),
                'responseStatus' => [
                    'successful' => $status === 'paid',
                    'status' => $status,
                    'code' => $status === 'paid' ? 200 : 400,
                    'message' => 'Simulated '.$status,
                    'source' => strtoupper($charge->method),
                ],
                'simulated' => true,
            ]);
        }

        $this->info("Charge {$charge->merchant_transaction_id} is now ".$charge->fresh()->status.'.');

        return self::SUCCESS;
    }
}
