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
    fakeAppyPay();
    $this->charge = BillingCharge::where('uuid',
        postJson('api/v1/billing/charges', ['plan' => 'business', 'months' => 3, 'method' => 'reference'])->json('data.uuid')
    )->firstOrFail();
});

function appyPayHook(BillingCharge $charge, bool $successful, string $status = 'paid', string $token = 'hook-secret')
{
    return postJson('api/webhooks/appypay?token='.$token, [
        'id' => 'prov-1',
        'merchantTransactionId' => $charge->merchant_transaction_id,
        'amount' => $charge->amountInKwanza(),
        'responseStatus' => ['successful' => $successful, 'status' => $status, 'code' => 200, 'message' => $status],
    ]);
}

test('a webhook with the wrong token is rejected', function () {
    appyPayHook($this->charge, true, token: 'wrong')->assertStatus(401);

    expect($this->charge->fresh()->status)->toBe(BillingCharge::PENDING);
});

test('a paid webhook activates the subscription from the end of the trial', function () {
    $trialEnd = $this->account['subscription']->trial_ends_at;

    appyPayHook($this->charge, true)->assertOk()->assertJson(['success' => true]);

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
    appyPayHook($this->charge, true)->assertOk();
    $end = Subscription::find($this->account['subscription']->id)->current_period_ends_at;

    appyPayHook($this->charge, true)->assertOk();
    appyPayHook($this->charge, false, 'failed')->assertOk();

    $charge = $this->charge->fresh();
    expect($charge->status)->toBe(BillingCharge::PAID)
        ->and($charge->webhook_events)->toHaveCount(3)
        ->and(Subscription::find($this->account['subscription']->id)->current_period_ends_at->equalTo($end))->toBeTrue();
});

test('failure statuses map to failed, cancelled and expired', function (string $status, string $expected) {
    appyPayHook($this->charge, false, $status)->assertOk();

    expect($this->charge->fresh()->status)->toBe($expected);
})->with([
    ['cancelled', BillingCharge::CANCELLED],
    ['canceled', BillingCharge::CANCELLED],
    ['expired', BillingCharge::EXPIRED],
    ['declined', BillingCharge::FAILED],
]);

test('an unknown transaction is acknowledged without changes', function () {
    postJson('api/webhooks/appypay?token=hook-secret', [
        'merchantTransactionId' => 'NOPE',
        'responseStatus' => ['successful' => true],
    ])->assertOk();
});

test('the simulator drives the same handler', function () {
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
    appyPayHook(BillingCharge::where('uuid', $uuid)->first(), true)->assertOk();
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
