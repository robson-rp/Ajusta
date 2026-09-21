<?php

use App\Domains\Billing\Http\Controllers\Webhook\AppyPayWebhookController;
use Illuminate\Support\Facades\Route;

// AppyPay status notifications. Authenticated by the ?token= shared secret.
Route::post('webhooks/appypay', AppyPayWebhookController::class)->name('billing.webhooks.appypay');
