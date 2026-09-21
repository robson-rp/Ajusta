<?php

namespace App\Domains\Billing\Application;

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Billing\Gateways\GatewayManager;
use App\Domains\Billing\Mail\ChargeReferenceMail;
use App\Domains\Billing\Models\BillingCharge;
use App\Domains\Billing\Models\Plan;
use App\Domains\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Creates charges and moves them through their states. A terminal charge
 * (paid, failed, cancelled, expired) never changes again; a charge becoming
 * paid extends the subscription exactly once.
 */
class ChargeService
{
    public function __construct(
        private readonly GatewayManager $gateways,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /** Monthly price plus the e-invoicing fee when it is switched on. */
    public function amountFor(Plan $plan, int $months): int
    {
        $monthly = $plan->price_monthly;

        if (config('billing.e_invoice_fee.enabled')) {
            $monthly += (int) config('billing.e_invoice_fee.amount');
        }

        return $monthly * $months;
    }

    /**
     * @param  array{phone?: string|null, name?: string|null, email?: string|null}  $customer
     */
    public function create(
        Subscription $subscription,
        Plan $plan,
        int $months,
        string $method,
        array $customer,
        ?Company $company = null,
        ?User $by = null,
    ): BillingCharge {
        if (! in_array($months, config('billing.periods'), true)) {
            throw new InvalidArgumentException('Invalid billing period.');
        }

        $gateway = $this->gateways->forMethod($method)
            ?? throw new InvalidArgumentException("Payment method [{$method}] is not available.");

        $amount = $this->amountFor($plan, $months);

        // Reuse an open reference for the same purchase instead of issuing a
        // second one the customer might pay twice.
        if ($method === 'reference') {
            $open = BillingCharge::where('subscription_id', $subscription->id)
                ->where('status', BillingCharge::PENDING)
                ->where('method', 'reference')
                ->where('plan_id', $plan->id)
                ->where('months', $months)
                ->where('amount', $amount)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('id')
                ->first();

            if ($open) {
                return $open;
            }
        }

        $charge = BillingCharge::create([
            'uuid' => (string) Str::uuid(),
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'company_id' => $company?->id,
            'created_by' => $by?->id,
            'months' => $months,
            'amount' => $amount,
            'currency' => config('billing.currency'),
            'gateway' => $gateway->key(),
            'method' => $method,
            'status' => BillingCharge::PENDING,
            'merchant_transaction_id' => $this->newMerchantTransactionId(),
            'customer_name' => $customer['name'] ?? null,
            'customer_email' => $customer['email'] ?? null,
            'customer_phone' => $customer['phone'] ?? null,
        ]);
        $charge->setRelation('plan', $plan);

        $result = $gateway->createCharge($charge, $customer);

        $charge->fill([
            'provider_id' => $result->providerId,
            'provider_successful' => $result->successful,
            'provider_code' => $result->code,
            'provider_message' => $result->message,
            'reference_number' => $result->referenceNumber,
            'entity_number' => $result->entityNumber,
            'expires_at' => $result->expiresAt,
            'status' => $result->successful ? BillingCharge::PENDING : BillingCharge::FAILED,
        ])->save();

        if ($result->successful && $method === 'reference' && $charge->customer_email) {
            try {
                Mail::to($charge->customer_email)->send(new ChargeReferenceMail($charge));
            } catch (Throwable $e) {
                Log::warning('Reference mail not sent', ['charge' => $charge->uuid, 'error' => $e->getMessage()]);
            }
        }

        return $charge;
    }

    /**
     * Payment recorded by the platform admin (transfer, cash, …).
     */
    public function recordManual(Subscription $subscription, Plan $plan, int $months, User $admin, ?string $note = null): BillingCharge
    {
        $charge = BillingCharge::create([
            'uuid' => (string) Str::uuid(),
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'created_by' => $admin->id,
            'months' => $months,
            'amount' => $this->amountFor($plan, $months),
            'currency' => config('billing.currency'),
            'gateway' => 'manual',
            'method' => 'manual',
            'status' => BillingCharge::PENDING,
            'merchant_transaction_id' => $this->newMerchantTransactionId(),
            'note' => $note,
        ]);

        return $this->transition($charge, BillingCharge::PAID, ['source' => 'admin', 'admin_id' => $admin->id]);
    }

    /**
     * Move a charge to a new state, keeping the raw event for audit. Terminal
     * charges only record the event. Becoming paid extends the subscription.
     */
    public function transition(BillingCharge $charge, string $status, array $event = []): BillingCharge
    {
        $becamePaid = false;

        DB::transaction(function () use ($charge, $status, $event, &$becamePaid) {
            $locked = BillingCharge::lockForUpdate()->findOrFail($charge->id);

            $events = $locked->webhook_events ?? [];
            if ($event !== []) {
                $events[] = ['received_at' => now()->toIso8601String(), 'payload' => $event];
            }
            $locked->webhook_events = $events;

            if (! $locked->isTerminal() && $status !== $locked->status) {
                $locked->status = $status;

                if ($status === BillingCharge::PAID) {
                    $locked->paid_at = now();
                    $becamePaid = true;
                }
            }

            $locked->save();
            $charge->setRawAttributes($locked->getAttributes(), true);
        });

        if ($becamePaid) {
            $this->subscriptions->applyPayment($charge);
        }

        return $charge;
    }

    /** Pending charges whose reference or GPO request has lapsed. */
    public function expireStale(): int
    {
        $count = 0;

        BillingCharge::where('status', BillingCharge::PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (BillingCharge $charge) use (&$count) {
                $this->transition($charge, BillingCharge::EXPIRED, ['source' => 'sweep']);
                $count++;
            });

        return $count;
    }

    /** Unique id for the provider: ≤15 chars, [A-Za-z0-9] only. */
    private function newMerchantTransactionId(): string
    {
        do {
            $id = 'AJ'.strtoupper(base_convert((string) now()->getTimestampMs(), 10, 36)).Str::upper(Str::random(4));
            $id = substr(preg_replace('/[^A-Za-z0-9]/', '', $id), 0, 15);
        } while (BillingCharge::where('merchant_transaction_id', $id)->exists());

        return $id;
    }
}
