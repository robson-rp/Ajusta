<?php

use App\Domains\Billing\Http\Controllers\Company\BillingController;
use Illuminate\Support\Facades\Route;

// The account's subscription. Only the account owner can pay.
Route::get('billing', [BillingController::class, 'show']);
Route::post('billing/charges', [BillingController::class, 'store'])->middleware('throttle:10,1');
Route::get('billing/charges/{charge}', [BillingController::class, 'charge']);
