<?php

namespace App\Domains\Billing\Application;

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Billing\Mail\PaymentReceivedMail;
use App\Domains\Billing\Mail\RenewalDueMail;
use App\Domains\Billing\Mail\SubscriptionSuspendedMail;
use App\Domains\Billing\Mail\TrialEndingMail;
use App\Domains\Billing\Models\BillingCharge;
use App\Domains\Billing\Models\Plan;
use App\Domains\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The subscription lifecycle: trial, payment, renewal notices, grace
 * period and suspension. A subscription belongs to the account owner and
 * covers every company that owner runs.
 */
class SubscriptionService
{
    /** The subscription governing a company (its owner's), if any. */
    public function forCompany(Company|int|string|null $company): ?Subscription
    {
        if ($company === null || $company === '') {
            return null;
        }

        $ownerId = $company instanceof Company
            ? $company->owner_id
            : Company::query()->whereKey($company)->value('owner_id');

        return $ownerId ? Subscription::with('plan')->where('user_id', $ownerId)->first() : null;
    }

    public function forUser(User $user): ?Subscription
    {
        return Subscription::with('plan')->where('user_id', $user->id)->first();
    }

    public function startTrial(User $owner, Plan $plan): Subscription
    {
        return Subscription::create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'status' => Subscription::TRIALING,
            'trial_ends_at' => now()->addDays((int) config('billing.trial_days'))->endOfDay(),
        ]);
    }

    /**
     * Extend the paid period by the charge's months, counted from the end of
     * the current period (or trial) when that is still in the future.
     */
    public function applyPayment(BillingCharge $charge): Subscription
    {
        $subscription = DB::transaction(function () use ($charge) {
            $subscription = Subscription::lockForUpdate()->findOrFail($charge->subscription_id);

            $from = $subscription->endsAt();
            $start = ($from !== null && $from->isFuture()) ? $from->copy() : now();

            $subscription->fill([
                'plan_id' => $charge->plan_id,
                'status' => Subscription::ACTIVE,
                'current_period_ends_at' => $start->addMonthsNoOverflow($charge->months)->endOfDay(),
                'grace_ends_at' => null,
                'notices' => [],
            ])->save();

            return $subscription;
        });

        $this->mail($subscription, new PaymentReceivedMail($charge->fresh(['plan', 'subscription'])));

        return $subscription;
    }

    public function changePlan(Subscription $subscription, Plan $plan): Subscription
    {
        $subscription->update(['plan_id' => $plan->id]);

        return $subscription->fresh('plan');
    }

    public function suspend(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => Subscription::SUSPENDED]);

        return $subscription;
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => Subscription::CANCELLED]);

        return $subscription;
    }

    /**
     * Daily pass over every subscription: reminders, end of period, end of
     * grace. Safe to run more than once a day.
     *
     * @return array<string, int>
     */
    public function sweep(): array
    {
        $counts = ['trial_reminders' => 0, 'renewal_notices' => 0, 'past_due' => 0, 'suspended' => 0];
        $noticeDays = (int) config('billing.notice_days');
        $graceDays = (int) config('billing.grace_days');

        Subscription::with(['plan', 'user'])
            ->whereIn('status', [Subscription::TRIALING, Subscription::ACTIVE, Subscription::PAST_DUE])
            ->chunkById(100, function ($subscriptions) use (&$counts, $noticeDays, $graceDays) {
                foreach ($subscriptions as $subscription) {
                    $end = $subscription->endsAt();

                    if ($subscription->status === Subscription::PAST_DUE) {
                        if ($subscription->grace_ends_at !== null && $subscription->grace_ends_at->isPast()) {
                            $this->suspend($subscription);
                            $this->mail($subscription, new SubscriptionSuspendedMail($subscription));
                            $counts['suspended']++;
                        }

                        continue;
                    }

                    if ($end === null) {
                        continue;
                    }

                    if ($end->isPast()) {
                        $subscription->update([
                            'status' => Subscription::PAST_DUE,
                            'grace_ends_at' => now()->addDays($graceDays)->endOfDay(),
                        ]);
                        $counts['past_due']++;

                        continue;
                    }

                    $daysLeft = (int) floor(now()->diffInDays($end, false));

                    if ($subscription->status === Subscription::TRIALING) {
                        foreach (config('billing.trial_reminder_days', []) as $day) {
                            $key = 'trial_'.$day;
                            if ($daysLeft <= $day && ! $subscription->hasNotice($key)) {
                                $subscription->markNotice($key);
                                $this->mail($subscription, new TrialEndingMail($subscription));
                                $counts['trial_reminders']++;
                                break;
                            }
                        }
                    } elseif ($daysLeft <= $noticeDays && ! $subscription->hasNotice('renewal')) {
                        $subscription->markNotice('renewal');
                        $this->mail($subscription, new RenewalDueMail($subscription));
                        $counts['renewal_notices']++;
                    }
                }
            });

        return $counts;
    }

    private function mail(Subscription $subscription, $mailable): void
    {
        $email = $subscription->user?->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (Throwable $e) {
            // A mail outage must never block a payment or the daily sweep.
            Log::warning('Billing mail not sent', ['subscription' => $subscription->id, 'error' => $e->getMessage()]);
        }
    }
}
