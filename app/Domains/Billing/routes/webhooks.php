<?php

use App\Domains\Billing\Http\Controllers\Webhook\AppyPayWebhookController;
use App\Domains\Billing\Http\Controllers\Webhook\StrongPayWebhookController;
use Illuminate\Support\Facades\Route;

// StrongPay payment.updated notifications. Authenticated by the HMAC signature.
Route::post('webhooks/strongpay', StrongPayWebhookController::class)->name('billing.webhooks.strongpay');

// AppyPay status notifications. Authenticated by the ?token= shared secret.
Route::post('webhooks/appypay', AppyPayWebhookController::class)->name('billing.webhooks.appypay');
