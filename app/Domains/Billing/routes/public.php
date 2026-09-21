<?php

use App\Domains\Billing\Http\Controllers\Public\SignupController;
use Illuminate\Support\Facades\Route;

// Self-service signup: company + owner + free trial.
Route::get('signup/plans', [SignupController::class, 'plans']);
Route::post('signup', [SignupController::class, 'register'])->middleware('throttle:10,1');
