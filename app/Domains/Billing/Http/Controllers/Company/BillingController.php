<?php

namespace App\Domains\Billing\Http\Controllers\Company;

use App\Domains\Accounts\Models\Company;
use App\Domains\Billing\Application\ChargeService;
use App\Domains\Billing\Application\SubscriptionService;
use App\Domains\Billing\Gateways\GatewayManager;
use App\Domains\Billing\Http\Requests\CreateChargeRequest;
use App\Domains\Billing\Http\Resources\ChargeResource;
use App\Domains\Billing\Http\Resources\PlanResource;
use App\Domains\Billing\Http\Resources\SubscriptionResource;
use App\Domains\Billing\Models\BillingCharge;
use App\Domains\Billing\Models\Plan;
use App\Domains\Billing\Models\Subscription;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * The account owner's subscription page: status, checkout and charge status.
 */
class BillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly ChargeService $charges,
        private readonly GatewayManager $gateways,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->forCompany($request->header('company'));

        return response()->json([
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            'can_manage' => $subscription !== null && $subscription->user_id === $request->user()->id,
            'plans' => PlanResource::collection(Plan::public()->get()),
            'periods' => config('billing.periods'),
            'options' => $this->gateways->options(),
            'charges' => $subscription
                ? ChargeResource::collection($subscription->charges()->with('plan')->limit(20)->get())
                : [],
        ]);
    }

    public function store(CreateChargeRequest $request): JsonResponse
    {
        $subscription = $this->ownedSubscription($request);
        $plan = Plan::where('code', $request->input('plan'))->firstOrFail();

        try {
            $charge = $this->charges->create(
                $subscription,
                $plan,
                (int) $request->input('months'),
                (string) $request->input('method'),
                [
                    'phone' => $request->normalizedPhone(),
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
                Company::find($request->header('company')),
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'invalid_method', 'message' => $e->getMessage()], 422);
        }

        return (new ChargeResource($charge->load('plan')))
            ->response()
            ->setStatusCode($charge->status === BillingCharge::FAILED ? 422 : 201);
    }

    public function charge(Request $request, BillingCharge $charge): ChargeResource
    {
        abort_unless($charge->subscription_id === $this->ownedSubscription($request)->id, 404);

        return new ChargeResource($charge->load('plan'));
    }

    private function ownedSubscription(Request $request): Subscription
    {
        $subscription = $this->subscriptions->forCompany($request->header('company'));

        abort_if($subscription === null, 404);
        abort_unless($subscription->user_id === $request->user()->id, 403, __('billing.errors.owner_only'));

        return $subscription;
    }
}
