<?php

namespace App\Domains\Billing\Application;

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\CompanyInvitation;
use App\Domains\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Plan limits and switches. Accounts without a subscription (existing
 * installs, companies created by the platform admin) have no limits.
 */
class PlanFeatures
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function allows(?Subscription $subscription, string $feature): bool
    {
        if ($subscription === null || $subscription->plan === null) {
            return true;
        }

        return (bool) $subscription->plan->feature($feature, false);
    }

    /** Can the owner of this company create one more company? */
    public function canAddCompany(int $ownerId): bool
    {
        $subscription = Subscription::with('plan')->where('user_id', $ownerId)->first();
        $limit = $subscription?->plan?->feature('max_companies');

        return $limit === null || Company::where('owner_id', $ownerId)->count() < $limit;
    }

    /** Can one more person be invited into this company's account? */
    public function canAddMember(Company|int|string $company): bool
    {
        $subscription = $this->subscriptions->forCompany($company);
        $limit = $subscription?->plan?->feature('max_users');

        if ($limit === null) {
            return true;
        }

        $companyIds = Company::where('owner_id', $subscription->user_id)->pluck('id');
        $members = DB::table('user_company')->whereIn('company_id', $companyIds)->distinct()->count('user_id');
        $pending = CompanyInvitation::whereIn('company_id', $companyIds)
            ->where('status', CompanyInvitation::STATUS_PENDING)
            ->count();

        return $members + $pending < $limit;
    }
}
