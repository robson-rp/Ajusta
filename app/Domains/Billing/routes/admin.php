<?php

use App\Domains\Billing\Http\Controllers\Admin\PlansController;
use App\Domains\Billing\Http\Controllers\Admin\SubscriptionsController;
use Illuminate\Support\Facades\Route;

Route::get('billing/subscriptions', [SubscriptionsController::class, 'index']);
Route::get('billing/subscriptions/{subscription}', [SubscriptionsController::class, 'show']);
Route::put('billing/subscriptions/{subscription}', [SubscriptionsController::class, 'update']);
Route::post('billing/subscriptions/{subscription}/payments', [SubscriptionsController::class, 'recordPayment']);
Route::get('billing/plans', [PlansController::class, 'index']);
Route::put('billing/plans/{plan}', [PlansController::class, 'update']);
