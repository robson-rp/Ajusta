<?php

use App\Domains\Accounts\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

require_once __DIR__.'/BillingTestHelpers.php';

test('the Start plan blocks recurring invoices, the customer portal, members and extra companies', function () {
    billingSignup('start');

    postJson('api/v1/recurring-invoices', [])->assertStatus(403)->assertJsonPath('feature', 'recurring_invoices');
    postJson('api/v1/customers', ['name' => 'Cliente', 'enable_portal' => true])->assertStatus(403)
        ->assertJsonPath('feature', 'customer_portal');
    postJson('api/v1/company-invitations', ['email' => 'x@y.ao'])->assertStatus(403)->assertJsonPath('feature', 'max_users');
    postJson('api/v1/companies', ['name' => 'Outra'])->assertStatus(403)->assertJsonPath('feature', 'max_companies');

    // Plain customers are fine.
    postJson('api/v1/customers', ['name' => 'Cliente'])->assertSuccessful();
});

test('the Empresa plan allows them', function () {
    billingSignup('business');

    postJson('api/v1/customers', ['name' => 'Cliente', 'enable_portal' => false])->assertSuccessful();
    $response = postJson('api/v1/recurring-invoices', []);
    expect($response->status())->not->toBe(403);
});

test('bootstrap exposes the subscription of the account', function () {
    billingSignup('start');

    getJson('api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('current_company_subscription.status', 'trialing')
        ->assertJsonPath('current_company_subscription.plan.code', 'start')
        ->assertJsonPath('current_company_subscription.is_owner', true);
});

test('accounts without a subscription are not restricted', function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
    $user = User::find(1);
    Sanctum::actingAs($user, ['*']);
    $this->withHeaders(['company' => $user->companies()->first()->id]);

    getJson('api/v1/bootstrap')->assertOk()->assertJsonPath('current_company_subscription', null);
    postJson('api/v1/recurring-invoices', [])->assertStatus(422);
});
