<?php

namespace App\Domains\Billing\Console;

use App\Domains\Billing\Gateways\AppyPay\AppyPayWebhookHandler;
use App\Domains\Billing\Models\BillingCharge;
use Illuminate\Console\Command;

/**
 * Feeds a fake AppyPay notification through the real webhook handler, for
 * local testing without a public HTTPS URL. Refuses to run in production.
 */
class SimulateWebhook extends Command
{
    protected $signature = 'billing:simulate-webhook {charge : Charge uuid or merchant transaction id} {status=paid : paid|failed|cancelled|expired}';

    protected $description = 'Simulate an AppyPay webhook for a charge (local testing only)';

    public function handle(AppyPayWebhookHandler $handler): int
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

        $handler->handle([
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

        $this->info("Charge {$charge->merchant_transaction_id} is now ".$charge->fresh()->status.'.');

        return self::SUCCESS;
    }
}
