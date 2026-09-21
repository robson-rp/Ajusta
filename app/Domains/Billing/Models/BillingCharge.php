<?php

namespace App\Domains\Billing\Models;

use App\Domains\Accounts\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attempt to pay for a subscription period, as sent to a gateway.
 * Terminal states never change again; every webhook payload is kept in
 * `webhook_events` for audit.
 */
class BillingCharge extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const EXPIRED = 'expired';

    public const TERMINAL = [self::PAID, self::FAILED, self::CANCELLED, self::EXPIRED];

    protected $table = 'billing_charges';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'months' => 'integer',
            'provider_successful' => 'boolean',
            'provider_code' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'webhook_events' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL, true);
    }

    /** Amount in Kwanza (the gateways work in units, not cents). */
    public function amountInKwanza(): float
    {
        return round($this->amount / 100, 2);
    }
}
