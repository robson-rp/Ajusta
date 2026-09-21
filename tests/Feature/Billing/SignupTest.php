<?php

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Billing\Mail\WelcomeMail;
use App\Domains\Billing\Models\Subscription;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

require_once __DIR__.'/BillingTestHelpers.php';

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Mail::fake();
});

function signupPayload(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'Muxima Comércio, Lda',
        'tax_id' => '5417000009',
        'name' => 'João Kiala',
        'email' => 'joao@muxima.ao',
        'phone' => '+244 924 552 310',
        'password' => 'segredo123',
        'password_confirmation' => 'segredo123',
        'plan' => 'business',
    ], $overrides);
}

test('public plans are listed for the signup page', function () {
    getJson('api/v1/signup/plans')
        ->assertOk()
        ->assertJsonPath('trial_days', 14)
        ->assertJsonCount(2, 'plans')
        ->assertJsonPath('plans.0.code', 'start')
        ->assertJsonPath('plans.0.price_monthly', 600000)
        ->assertJsonPath('plans.1.code', 'business')
        ->assertJsonPath('plans.1.price_monthly', 2500000);
});

test('signup creates the owner, the company with its NIF and a trial', function () {
    $response = postJson('api/v1/signup', signupPayload())->assertCreated();

    expect($response->json('token'))->not->toBeEmpty();

    $user = User::where('email', 'joao@muxima.ao')->firstOrFail();
    $company = Company::findOrFail($response->json('company_id'));

    expect($company->tax_id)->toBe('5417000009')
        ->and($company->owner_id)->toBe($user->id)
        ->and($user->companies()->pluck('companies.id')->all())->toContain($company->id);

    $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
    expect($subscription->status)->toBe(Subscription::TRIALING)
        ->and($subscription->plan->code)->toBe('business')
        ->and((int) round(now()->diffInDays($subscription->trial_ends_at)))->toBe(14);

    Mail::assertSent(WelcomeMail::class);
});

test('signup rejects a NIF or e-mail already in use', function () {
    postJson('api/v1/signup', signupPayload())->assertCreated();

    postJson('api/v1/signup', signupPayload(['email' => 'outro@muxima.ao']))
        ->assertStatus(422)->assertJsonValidationErrors('tax_id');

    postJson('api/v1/signup', signupPayload(['tax_id' => '5417000010']))
        ->assertStatus(422)->assertJsonValidationErrors('email');
});

test('signup can be switched off', function () {
    config()->set('billing.signup_enabled', false);

    postJson('api/v1/signup', signupPayload())->assertForbidden();
});
