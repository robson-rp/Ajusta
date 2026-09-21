<?php

namespace App\Domains\Billing\Console;

use App\Domains\Billing\Application\ChargeService;
use App\Domains\Billing\Application\SubscriptionService;
use Illuminate\Console\Command;

class SweepSubscriptions extends Command
{
    protected $signature = 'billing:sweep';

    protected $description = 'Send trial and renewal reminders, move ended periods to grace, suspend unpaid accounts and expire stale charges';

    public function handle(SubscriptionService $subscriptions, ChargeService $charges): int
    {
        $counts = $subscriptions->sweep();
        $counts['expired_charges'] = $charges->expireStale();

        foreach ($counts as $label => $count) {
            $this->line(sprintf('%-18s %d', $label, $count));
        }

        return self::SUCCESS;
    }
}
