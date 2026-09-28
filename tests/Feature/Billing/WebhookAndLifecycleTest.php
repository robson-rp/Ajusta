<?php

use App\Domains\Billing\Mail\PaymentReceivedMail;
use App\Domains\Billing\Mail\SubscriptionSuspendedMail;
use App\Domains\Billing\Mail\TrialEndingMail;
use App\Domains\Billing\Models\BillingCharge;
use App\Domains\Billing\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

require_once __DIR__.'/BillingTestHelpers.php';

beforeEach(function () {
    Cache::flush();
    $this->account = billingSignup('start');
    fakeStrongPay();
    $this->charge = BillingCharge::where('uuid',
        postJson('api/v1/billing/charges', ['plan' => 'business', 'months' => 3, 'method' => 'reference'])->json('data.uuid')
    )->firstOrFail();
});

function paymentUpdated(BillingCharge $charge, string $status = 'paid', string $secret = 'hook-secret')
{
    return strongPayHook([
        'payment_id' => $charge->provider_id,
        'transaction_id' => 'appy-tx-1',
        'product' => 'ajusta',
        'status' => $status,
        'amount' => number_format($charge->amountInKwanza(), 2, '.', ''),
        'currency' => 'AOA',
        'payment_reference' => $charge->reference_number,
        'entity_number' => $charge->entity_number,
        'paid_at' => $status === 'paid' ? now()->toIso8601String() : null,
        'updated_at' => now()->toIso8601String(),
    ], $secret);
}

test('a webhook with a wrong signature is rejected', function () {
    paymentUpdated($this->charge, secret: 'wrong')->assertStatus(401);

    expect($this->charge->fresh()->status)->toBe(BillingCharge::PENDING);
});

test('an unsigned webhook is rejected', function () {
    postJson('api/webhooks/strongpay', ['event' => 'payment.updated', 'payment_id' => $this->charge->provider_id, 'status' => 'paid'])
        ->assertStatus(401);

    expect($this->charge->fresh()->status)->toBe(BillingCharge::PENDING);
});

test('webhooks are rejected while no secret is configured', function () {
    config()->set('services.strongpay.webhook_secret', null);

    paymentUpdated($this->charge)->assertStatus(401);

    expect($this->charge->fresh()->status)->toBe(BillingCharge::PENDING);
});

test('a paid webhook activates the subscription from the end of the trial', function () {
    $trialEnd = $this->account['subscription']->trial_ends_at;

    paymentUpdated($this->charge)->assertOk()->assertJson(['success' => true]);

    $charge = $this->charge->fresh();
    $subscription = Subscription::find($this->account['subscription']->id);

    expect($charge->status)->toBe(BillingCharge::PAID)
        ->and($charge->paid_at)->not->toBeNull()
        ->and($charge->webhook_events)->toHaveCount(1)
        ->and($subscription->status)->toBe(Subscription::ACTIVE)
        ->and($subscription->plan->code)->toBe('business')
        ->and($subscription->current_period_ends_at->toDateString())
        ->toBe($trialEnd->copy()->addMonthsNoOverflow(3)->toDateString());

    Mail::assertSent(PaymentReceivedMail::class);
});

test('repeated or late webhooks never change a paid charge or extend twice', function () {
    paymentUpdated($this->charge)->assertOk();
    $end = Subscription::find($this->account['subscription']->id)->current_period_ends_at;

    paymentUpdated($this->charge)->assertOk();
    paymentUpdated($this->charge, 'failed')->assertOk();

    $charge = $this->charge->fresh();
    expect($charge->status)->toBe(BillingCharge::PAID)
        ->and($charge->webhook_events)->toHaveCount(3)
        ->and(Subscription::find($this->account['subscription']->id)->current_period_ends_at->equalTo($end))->toBeTrue();
});

test('StrongPay statuses map onto the charge', function (string $status, string $expected) {
    paymentUpdated($this->charge, $status)->assertOk();

    expect($this->charge->fresh()->status)->toBe($expected);
})->with([
    ['pending', BillingCharge::PENDING],
    ['failed', BillingCharge::FAILED],
    ['cancelled', BillingCharge::CANCELLED],
    ['expired', BillingCharge::EXPIRED],
]);

test('an unknown payment is acknowledged without changes', function () {
    strongPayHook(['payment_id' => 'NOPE', 'status' => 'paid'])->assertOk();

    expect($this->charge->fresh()->status)->toBe(BillingCharge::PENDING);
});

test('the simulator drives the StrongPay handler for StrongPay charges', function () {
    Artisan::call('billing:simulate-webhook', ['charge' => $this->charge->uuid, 'status' => 'paid']);

    expect($this->charge->fresh()->status)->toBe(BillingCharge::PAID);
});

test('trial → reminder → past due → suspended, then payment reactivates', function () {
    $subscription = Subscription::find($this->account['subscription']->id);

    Carbon::setTestNow($subscription->trial_ends_at->copy()->subDays(2));
    Artisan::call('billing:sweep');
    Mail::assertSent(TrialEndingMail::class);

    Carbon::setTestNow($subscription->trial_ends_at->copy()->addHour());
    Artisan::call('billing:sweep');
    $subscription->refresh();
    expect($subscription->status)->toBe(Subscription::PAST_DUE)
        ->and($subscription->isWritable())->toBeTrue();

    Carbon::setTestNow($subscription->grace_ends_at->copy()->addHour());
    Artisan::call('billing:sweep');
    $subscription->refresh();
    expect($subscription->status)->toBe(Subscription::SUSPENDED)
        ->and($subscription->isWritable())->toBeFalse();
    Mail::assertSent(SubscriptionSuspendedMail::class);

    // Suspended: reads work, writes answer 402, billing stays open.
    getJson('api/v1/customers')->assertOk();
    postJson('api/v1/customers', ['name' => 'Novo cliente'])->assertStatus(402)
        ->assertJsonPath('error', 'subscription_inactive');
    getJson('api/v1/billing')->assertOk();

    // The old reference lapsed meanwhile; the owner pays a new one.
    expect($this->charge->fresh()->status)->toBe(BillingCharge::EXPIRED);
    $uuid = postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])
        ->assertCreated()->json('data.uuid');
    paymentUpdated(BillingCharge::where('uuid', $uuid)->first())->assertOk();
    $subscription->refresh();
    expect($subscription->status)->toBe(Subscription::ACTIVE)
        ->and($subscription->current_period_ends_at->isFuture())->toBeTrue();

    Carbon::setTestNow();
});

test('stale pending charges expire', function () {
    Carbon::setTestNow(now()->addDays(11));
    Artisan::call('billing:sweep');

    expect($this->charge->fresh()->status)->toBe(BillingCharge::EXPIRED);
    Carbon::setTestNow();
});
