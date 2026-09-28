<?php

use App\Domains\Billing\Models\BillingCharge;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

require_once __DIR__.'/BillingTestHelpers.php';

/*
 * The direct AppyPay gateway is not offered at checkout (StrongPay wraps it),
 * but stays usable when listed in billing.gateways.
 */

beforeEach(function () {
    Cache::flush();
    $this->account = billingSignup('start');
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

function appyPayReferenceCharge(): BillingCharge
{
    fakeAppyPay();

    return BillingCharge::where('uuid',
        postJson('api/v1/billing/charges', ['plan' => 'business', 'months' => 3, 'method' => 'reference'])->json('data.uuid')
    )->firstOrFail();
}

test('AppyPay offers only the reference while GPO is off', function () {
    fakeAppyPay();

    getJson('api/v1/billing')
        ->assertOk()
        ->assertJsonPath('options.0.gateway', 'appypay')
        ->assertJsonPath('options.0.method', 'reference')
        ->assertJsonCount(1, 'options');
});

test('an AppyPay reference charge is priced per month', function () {
    fakeAppyPay();

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 3, 'method' => 'reference'])
        ->assertCreated()
        ->assertJsonPath('data.gateway', 'appypay')
        ->assertJsonPath('data.entity_number', '00123')
        ->assertJsonPath('data.reference_number', '123456789')
        ->assertJsonPath('data.amount', 600000 * 3);

    Http::assertSent(function (Request $request) {
        return str_ends_with($request->url(), '/v2.0/charges')
            && $request['amount'] == 18000.0
            && $request['paymentMethod'] === 'REF_test'
            && ! isset($request['paymentInfo'])
            && strlen($request['merchantTransactionId']) <= 15
            && $request->hasHeader('Authorization', 'Bearer tok');
    });
});

test('AppyPay GPO needs a phone number and sends it without the country code', function () {
    fakeAppyPay(['id' => 'prov-2', 'responseStatus' => ['successful' => true, 'code' => 101, 'source' => 'GPO']]);
    enableGpo();

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo'])
        ->assertStatus(422)->assertJsonValidationErrors('phone');

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo', 'phone' => '+244 923 111 201'])
        ->assertCreated()
        ->assertJsonPath('data.method', 'gpo');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v2.0/charges')
        && $request['paymentMethod'] === 'GPO_test'
        && $request['paymentInfo']['phoneNumber'] === '923111201');
});

test('a charge refused by AppyPay is stored as failed', function () {
    fakeAppyPay(['id' => 'x', 'responseStatus' => ['successful' => false, 'code' => 400, 'message' => 'Invalid phone']]);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])
        ->assertStatus(422)
        ->assertJsonPath('data.status', 'failed');
});

test('the AppyPay token is cached and renewed once after a 401', function () {
    configureAppyPay();
    Http::fake([
        'login.example.test/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        'gwy-api-tst.appypay.co.ao/*' => Http::sequence()
            ->push(['message' => 'expired'], 401)
            ->push(['id' => 'p', 'responseStatus' => ['successful' => true, 'code' => 101, 'reference' => ['referenceNumber' => '1', 'entity' => '2']]])
            ->push(['id' => 'p2', 'responseStatus' => ['successful' => true, 'code' => 101, 'reference' => ['referenceNumber' => '3', 'entity' => '2']]]),
    ]);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->assertCreated();
    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 3, 'method' => 'reference'])->assertCreated();

    $tokenCalls = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'login.example.test'))->count();
    expect($tokenCalls)->toBe(2); // first fetch + one renewal after the 401
});

test('an AppyPay webhook with the wrong token is rejected', function () {
    $charge = appyPayReferenceCharge();

    appyPayHook($charge, true, token: 'wrong')->assertStatus(401);

    expect($charge->fresh()->status)->toBe(BillingCharge::PENDING);
});

test('an AppyPay paid webhook pays the charge', function () {
    $charge = appyPayReferenceCharge();

    appyPayHook($charge, true)->assertOk()->assertJson(['success' => true]);

    expect($charge->fresh()->status)->toBe(BillingCharge::PAID);
});

test('AppyPay failure statuses map to failed, cancelled and expired', function (string $status, string $expected) {
    $charge = appyPayReferenceCharge();

    appyPayHook($charge, false, $status)->assertOk();

    expect($charge->fresh()->status)->toBe($expected);
})->with([
    ['cancelled', BillingCharge::CANCELLED],
    ['canceled', BillingCharge::CANCELLED],
    ['expired', BillingCharge::EXPIRED],
    ['declined', BillingCharge::FAILED],
]);

test('an unknown AppyPay transaction is acknowledged without changes', function () {
    configureAppyPay();

    postJson('api/webhooks/appypay?token=hook-secret', [
        'merchantTransactionId' => 'NOPE',
        'responseStatus' => ['successful' => true],
    ])->assertOk();
});

test('the simulator drives the AppyPay handler for AppyPay charges', function () {
    $charge = appyPayReferenceCharge();

    Artisan::call('billing:simulate-webhook', ['charge' => $charge->uuid, 'status' => 'paid']);

    expect($charge->fresh()->status)->toBe(BillingCharge::PAID);
});
