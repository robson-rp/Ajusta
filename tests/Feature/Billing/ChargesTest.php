<?php

use App\Domains\Accounts\Models\User;
use App\Domains\Billing\Models\BillingCharge;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

require_once __DIR__.'/BillingTestHelpers.php';

beforeEach(function () {
    Cache::flush();
    $this->account = billingSignup('start');
});

test('the owner sees plans, periods and the StrongPay reference method', function () {
    fakeStrongPay();

    getJson('api/v1/billing')
        ->assertOk()
        ->assertJsonPath('can_manage', true)
        ->assertJsonPath('subscription.status', 'trialing')
        ->assertJsonPath('periods', [1, 3, 6, 12])
        ->assertJsonPath('options.0.gateway', 'strongpay')
        ->assertJsonPath('options.0.method', 'reference')
        ->assertJsonCount(1, 'options');
});

test('no gateway is offered without a StrongPay API key', function () {
    config()->set('billing.gateways', []);

    getJson('api/v1/billing')->assertOk()->assertJsonCount(0, 'options');
});

test('GPO is not offered at checkout until it is enabled', function () {
    fakeStrongPay();

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo', 'phone' => '923111201'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('method');

    Http::assertNothingSent();
});

test('a reference charge returns entity and reference and is priced per month', function () {
    fakeStrongPay();

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 3, 'method' => 'reference'])
        ->assertCreated()
        ->assertJsonPath('data.gateway', 'strongpay')
        ->assertJsonPath('data.method', 'reference')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.entity_number', '11466')
        ->assertJsonPath('data.reference_number', '360886123')
        ->assertJsonPath('data.amount', 600000 * 3);

    expect(BillingCharge::first()->provider_id)->toBe('sp-pay-1')
        ->and(BillingCharge::first()->expires_at->isFuture())->toBeTrue();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://strongpay.example.test/api/v1/payments'
            && $request->method() === 'POST'
            && $request['method'] === 'ref'
            && $request['amount'] == 18000.0
            && $request['currency'] === 'AOA'
            && $request['product'] === 'ajusta'
            && $request['description'] === 'Ajusta Start 3m'
            && ! isset($request['customer_phone'])
            && $request->hasHeader('Authorization', 'Bearer sp_test');
    });
});

test('an open reference for the same purchase is reused', function () {
    fakeStrongPay();

    $first = postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->json('data.uuid');
    $second = postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->json('data.uuid');

    expect($second)->toBe($first)
        ->and(BillingCharge::count())->toBe(1);
    Http::assertSentCount(1);
});

test('GPO, once enabled, sends the phone with the country code', function () {
    fakeStrongPay(['id' => 'sp-pay-2', 'method' => 'multicaixa_express', 'status' => 'pending', 'provider_successful' => true, 'provider_code' => 101]);
    enableGpo();

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo'])
        ->assertStatus(422)->assertJsonValidationErrors('phone');

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo', 'phone' => '923 111 201'])
        ->assertCreated()
        ->assertJsonPath('data.method', 'gpo')
        ->assertJsonPath('data.status', 'pending');

    Http::assertSent(fn (Request $request) => $request['method'] === 'gpo'
        && $request['customer_phone'] === '+244923111201');
});

test('a charge refused by StrongPay is stored as failed', function () {
    fakeStrongPay([
        'error' => 'PROVIDER_ERROR',
        'provider_successful' => false,
        'provider_code' => 400,
        'provider_message' => 'Campo [Description] está em um formato inválido',
    ], 422);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])
        ->assertStatus(422)
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.message', 'Campo [Description] está em um formato inválido');
});

test('a validation error from StrongPay is stored as failed', function () {
    fakeStrongPay(['detail' => [['loc' => ['body', 'amount'], 'msg' => 'Field required']]], 422);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])
        ->assertStatus(422)
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.message', 'Field required');
});

test('an unreachable StrongPay fails the charge', function () {
    configureStrongPay();
    Http::fake(['strongpay.example.test/*' => Http::failedConnection()]);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])
        ->assertStatus(422)
        ->assertJsonPath('data.status', 'failed');
});

test('only the account owner can pay', function () {
    fakeStrongPay();
    $member = User::factory()->create();
    $member->companies()->attach($this->account['company']->id);
    Sanctum::actingAs($member, ['*']);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->assertForbidden();
});
