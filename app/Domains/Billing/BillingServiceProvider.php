<?php

namespace App\Domains\Billing;

use App\Domains\Billing\Console\SimulateWebhook;
use App\Domains\Billing\Console\SweepSubscriptions;
use App\Domains\Billing\Gateways\GatewayManager;
use Illuminate\Support\ServiceProvider;

/**
 * AJUSTA subscriptions: signup, plans, payment gateways and the daily
 * subscription sweep.
 */
class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GatewayManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SweepSubscriptions::class,
                SimulateWebhook::class,
            ]);
        }
    }
}
