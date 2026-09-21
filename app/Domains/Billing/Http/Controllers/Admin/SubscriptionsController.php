<?php

namespace App\Domains\Billing\Http\Controllers\Admin;

use App\Domains\Accounts\Models\Company;
use App\Domains\Billing\Application\ChargeService;
use App\Domains\Billing\Application\SubscriptionService;
use App\Domains\Billing\Http\Resources\ChargeResource;
use App\Domains\Billing\Http\Resources\SubscriptionResource;
use App\Domains\Billing\Models\Plan;
use App\Domains\Billing\Models\Subscription;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Platform admin view of every subscription, with manual actions for
 * payments outside the integrated gateways.
 */
class SubscriptionsController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly ChargeService $charges,
    ) {}

    public function index(Request $request)
    {
        $page = Subscription::with(['plan', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->input('search').'%';
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest('id')
            ->paginate($request->integer('limit', 15));

        $companies = Company::whereIn('owner_id', $page->pluck('user_id'))
            ->get(['id', 'name', 'tax_id', 'owner_id'])
            ->groupBy('owner_id');

        return SubscriptionResource::collection($page)->additional([
            'companies' => $companies,
        ]);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load(['plan', 'user']);

        return response()->json([
            'subscription' => new SubscriptionResource($subscription),
            'companies' => Company::where('owner_id', $subscription->user_id)->get(['id', 'name', 'tax_id']),
            'charges' => ChargeResource::collection($subscription->charges()->with('plan')->get()),
        ]);
    }

    public function recordPayment(Request $request, Subscription $subscription): SubscriptionResource
    {
        $data = $request->validate([
            'plan' => ['required', Rule::exists('plans', 'code')],
            'months' => ['required', 'integer', Rule::in(config('billing.periods'))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->charges->recordManual(
            $subscription,
            Plan::where('code', $data['plan'])->firstOrFail(),
            (int) $data['months'],
            $request->user(),
            $data['note'] ?? null,
        );

        return new SubscriptionResource($subscription->fresh(['plan', 'user']));
    }

    public function update(Request $request, Subscription $subscription): SubscriptionResource
    {
        $data = $request->validate([
            'plan' => ['sometimes', Rule::exists('plans', 'code')],
            'status' => ['sometimes', Rule::in([Subscription::ACTIVE, Subscription::SUSPENDED, Subscription::CANCELLED])],
        ]);

        if (isset($data['plan'])) {
            $this->subscriptions->changePlan($subscription, Plan::where('code', $data['plan'])->firstOrFail());
        }

        match ($data['status'] ?? null) {
            Subscription::SUSPENDED => $this->subscriptions->suspend($subscription),
            Subscription::CANCELLED => $this->subscriptions->cancel($subscription),
            Subscription::ACTIVE => $subscription->update(['status' => Subscription::ACTIVE, 'grace_ends_at' => null]),
            default => null,
        };

        return new SubscriptionResource($subscription->fresh(['plan', 'user']));
    }
}
