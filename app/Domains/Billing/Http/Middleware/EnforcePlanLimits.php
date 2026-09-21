<?php

namespace App\Domains\Billing\Http\Middleware;

use App\Domains\Accounts\Models\Company;
use App\Domains\Billing\Application\PlanFeatures;
use App\Domains\Billing\Application\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plan limits, checked in one place so upstream route files stay untouched:
 * - inviting or adding members        → max_users
 * - creating another company          → max_companies
 * - creating recurring invoices       → recurring_invoices
 * - turning on a customer's portal    → customer_portal
 */
class EnforcePlanLimits
{
    public function __construct(
        private readonly PlanFeatures $features,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->user()?->isSuperAdmin()) {
            return $next($request);
        }

        $companyId = $request->header('company');
        $blocked = null;

        if ($request->isMethod('post') && $request->is('api/v1/company-invitations', 'api/v1/members')) {
            $blocked = $this->features->canAddMember($companyId) ? null : 'max_users';
        } elseif ($request->isMethod('post') && $request->is('api/v1/companies')) {
            $ownerId = Company::whereKey($companyId)->value('owner_id') ?? $request->user()?->id;
            $blocked = $this->features->canAddCompany((int) $ownerId) ? null : 'max_companies';
        } elseif ($request->isMethod('post') && $request->is('api/v1/recurring-invoices')) {
            $blocked = $this->allows($companyId, 'recurring_invoices') ? null : 'recurring_invoices';
        } elseif ($request->is('api/v1/customers', 'api/v1/customers/*') && $request->boolean('enable_portal')) {
            $blocked = $this->allows($companyId, 'customer_portal') ? null : 'customer_portal';
        }

        if ($blocked !== null) {
            return response()->json([
                'error' => 'plan_feature',
                'feature' => $blocked,
                'message' => __('billing.errors.plan_feature'),
            ], 403);
        }

        return $next($request);
    }

    private function allows(mixed $companyId, string $feature): bool
    {
        return $this->features->allows($this->subscriptions->forCompany($companyId), $feature);
    }
}
