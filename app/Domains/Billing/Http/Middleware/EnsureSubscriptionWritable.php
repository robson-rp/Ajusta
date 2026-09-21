<?php

namespace App\Domains\Billing\Http\Middleware;

use App\Domains\Billing\Application\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only mode for accounts whose trial or paid period (plus grace) is
 * over: reads keep working, writes answer 402 until a payment lands.
 * Billing, profile and session routes stay open so the owner can pay.
 */
class EnsureSubscriptionWritable
{
    private const ALWAYS_OPEN = [
        'api/v1/billing/*',
        'api/v1/bootstrap',
        'api/v1/me',
        'api/v1/me/*',
        'api/v1/auth/logout',
        'api/v1/invitations/*',
    ];

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->is(...self::ALWAYS_OPEN)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || $user->isSuperAdmin()) {
            return $next($request);
        }

        $subscription = $this->subscriptions->forCompany($request->header('company'));

        if ($subscription !== null && ! $subscription->isWritable()) {
            return response()->json([
                'error' => 'subscription_inactive',
                'message' => __('billing.errors.subscription_inactive'),
                'status' => $subscription->status,
            ], 402);
        }

        return $next($request);
    }
}
