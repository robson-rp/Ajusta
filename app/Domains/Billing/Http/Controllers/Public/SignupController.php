<?php

namespace App\Domains\Billing\Http\Controllers\Public;

use App\Domains\Billing\Application\SignupService;
use App\Domains\Billing\Http\Requests\SignupRequest;
use App\Domains\Billing\Http\Resources\PlanResource;
use App\Domains\Billing\Models\Plan;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;

class SignupController extends Controller
{
    /** Public plans for the signup page. */
    public function plans(): JsonResponse
    {
        return response()->json([
            'enabled' => (bool) config('billing.signup_enabled'),
            'trial_days' => (int) config('billing.trial_days'),
            'plans' => PlanResource::collection(Plan::public()->get()),
        ]);
    }

    public function register(SignupRequest $request, SignupService $signup): JsonResponse
    {
        $result = $signup->register($request->validated());

        return response()->json([
            'type' => 'Bearer',
            'token' => $result['user']->createToken('web')->plainTextToken,
            'company_id' => $result['company']->id,
        ], 201);
    }
}
