<?php

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Billing\Application\SignupService;
use App\Domains\Billing\Models\Subscription;
use App\Domains\Money\Models\Currency;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

/**
 * Seeds reference data and signs up an owner with a trial, acting as them.
 *
 * @return array{user: User, company: Company, subscription: Subscription}
 */
function billingSignup(string $plan = 'start', string $nif = '5417000001', string $email = 'dono@kilamba.ao'): array
{
    Mail::fake();

    if (Currency::count() === 0) {
        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    }

    $result = app(SignupService::class)->register([
        'company_name' => 'Kilamba Serviços '.$nif,
        'tax_id' => $nif,
        'name' => 'Ana Mendes',
        'email' => $email,
        'phone' => '923000000',
        'password' => 'segredo123',
        'plan' => $plan,
    ]);

    Sanctum::actingAs($result['user'], ['*']);
    test()->withHeaders(['company' => $result['company']->id]);

    return $result;
}

function configureStrongPay(): void
{
    config()->set('services.strongpay', [
        'base_url' => 'https://strongpay.example.test',
        'api_key' => 'sp_test',
        'webhook_secret' => 'hook-secret',
    ]);
    config()->set('billing.gateways', ['strongpay']);
}

function fakeStrongPay(array $paymentResponse = [], int $status = 201): void
{
    configureStrongPay();

    if ($paymentResponse !== []) {
        Http::fake(['strongpay.example.test/*' => Http::response($paymentResponse, $status)]);

        return;
    }

    $sequence = 0;

    Http::fake([
        'strongpay.example.test/*' => function () use (&$sequence, $status) {
            return Http::response([
                'id' => 'sp-pay-'.(++$sequence),
                'transaction_id' => 'appy-tx-1',
                'provider_id' => 'appy-prov-1',
                'product' => 'ajusta',
                'method' => 'reference',
                'status' => 'pending',
                'amount' => '18000.00',
                'currency' => 'AOA',
                'payment_reference' => '360886123',
                'entity_number' => '11466',
                'expires_at' => now()->addMinutes(45)->toIso8601String(),
                'provider_successful' => true,
                'provider_status' => 'SUCCESSFUL',
                'provider_code' => 101,
                'provider_message' => 'The request has been accepted for processing.',
            ], $status);
        },
    ]);
}

/** POSTs a signed StrongPay payment.updated notification. */
function strongPayHook(array $payload, string $secret = 'hook-secret')
{
    $payload = ['event' => 'payment.updated'] + $payload;
    $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), $secret);

    return test()->postJson('api/webhooks/strongpay', $payload, ['X-StrongPay-Signature' => $signature]);
}

function configureAppyPay(): void
{
    config()->set('services.appypay', [
        'token_url' => 'https://login.example.test/oauth2/token',
        'client_id' => 'client',
        'client_secret' => 'secret',
        'resource' => 'resource',
        'base_url' => 'https://gwy-api-tst.appypay.co.ao',
        'method_gpo' => 'GPO_test',
        'method_reference' => 'REF_test',
        'webhook_token' => 'hook-secret',
        'reference_valid_days' => 10,
    ]);
    config()->set('billing.gateways', ['appypay']);
}

function enableGpo(): void
{
    config()->set('billing.gpo_enabled', true);
}

function fakeAppyPay(array $chargeResponse = [], int $chargeStatus = 200): void
{
    configureAppyPay();

    Http::fake([
        'login.example.test/*' => Http::response(['access_token' => 'tok', 'token_type' => 'Bearer', 'expires_in' => 3600]),
        'gwy-api-tst.appypay.co.ao/*' => Http::response($chargeResponse ?: [
            'id' => 'prov-1',
            'responseStatus' => [
                'successful' => true,
                'code' => 101,
                'message' => 'The request has been accepted for processing.',
                'source' => 'REF',
                'reference' => [
                    'referenceNumber' => '123456789',
                    'dueDate' => now()->addDays(10)->format('Y-m-d\TH:i:s'),
                    'entity' => '00123',
                ],
            ],
        ], $chargeStatus),
    ]);
}
