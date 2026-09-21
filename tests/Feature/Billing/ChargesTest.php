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

test('the owner sees plans, periods and the AppyPay methods', function () {
    fakeAppyPay();

    getJson('api/v1/billing')
        ->assertOk()
        ->assertJsonPath('can_manage', true)
        ->assertJsonPath('subscription.status', 'trialing')
        ->assertJsonPath('periods', [1, 3, 6, 12])
        ->assertJsonPath('options.0.method', 'gpo')
        ->assertJsonPath('options.1.method', 'reference');
});

test('a reference charge returns entity and reference and is priced per month', function () {
    fakeAppyPay();

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 3, 'method' => 'reference'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.entity_number', '00123')
        ->assertJsonPath('data.reference_number', '123456789')
        ->assertJsonPath('data.amount', 1500000 * 3);

    Http::assertSent(function (Request $request) {
        return str_ends_with($request->url(), '/v2.0/charges')
            && $request['amount'] == 45000.0
            && $request['paymentMethod'] === 'REF_test'
            && ! isset($request['paymentInfo'])
            && strlen($request['merchantTransactionId']) <= 15
            && $request->hasHeader('Authorization', 'Bearer tok');
    });
});

test('an open reference for the same purchase is reused', function () {
    fakeAppyPay();

    $first = postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->json('data.uuid');
    $second = postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->json('data.uuid');

    expect($second)->toBe($first)
        ->and(BillingCharge::count())->toBe(1);
});

test('GPO needs a phone number and sends it without the country code', function () {
    fakeAppyPay(['id' => 'prov-2', 'responseStatus' => ['successful' => true, 'code' => 101, 'source' => 'GPO']]);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo'])
        ->assertStatus(422)->assertJsonValidationErrors('phone');

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo', 'phone' => '+244 923 111 201'])
        ->assertCreated()
        ->assertJsonPath('data.method', 'gpo')
        ->assertJsonPath('data.status', 'pending');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v2.0/charges')
        && $request['paymentMethod'] === 'GPO_test'
        && $request['paymentInfo']['phoneNumber'] === '923111201');
});

test('a charge refused by AppyPay is stored as failed', function () {
    fakeAppyPay(['id' => 'x', 'responseStatus' => ['successful' => false, 'code' => 400, 'message' => 'Invalid phone']]);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'gpo', 'phone' => '923111201'])
        ->assertStatus(422)
        ->assertJsonPath('data.status', 'failed');
});

test('the token is cached and renewed once after a 401', function () {
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

test('only the account owner can pay', function () {
    fakeAppyPay();
    $member = User::factory()->create();
    $member->companies()->attach($this->account['company']->id);
    Sanctum::actingAs($member, ['*']);

    postJson('api/v1/billing/charges', ['plan' => 'start', 'months' => 1, 'method' => 'reference'])->assertForbidden();
});
