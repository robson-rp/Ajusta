<?php

use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Accounts\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::find(1);
    $this->companyId = $this->user->companies()->first()->id;
    $this->withHeaders(['company' => $this->companyId]);
    Sanctum::actingAs($this->user, ['*']);
});

test('validation messages follow the user language', function () {
    $this->user->setSettings(['language' => 'pt_AO']);

    postJson('api/v1/customers', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.name.0', 'O campo nome é obrigatório.');
});

test('a user on the default language inherits the company language', function () {
    $this->user->setSettings(['language' => 'default']);
    CompanySetting::setSettings(['language' => 'pt_AO'], $this->companyId);

    postJson('api/v1/customers', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.name.0', 'O campo nome é obrigatório.');
});

test('English stays English', function () {
    $this->user->setSettings(['language' => 'en']);

    postJson('api/v1/customers', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.name.0', 'The name field is required.');
});
