<?php

namespace App\Domains\Billing\Models;

use App\Domains\Accounts\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The account's subscription. It belongs to the account owner, so every
 * company the owner runs shares one plan and one billing period.
 *
 * trialing  → free trial, until trial_ends_at
 * active    → paid, until current_period_ends_at
 * past_due  → period (or trial) over, still writable until grace_ends_at
 * suspended → read-only until a payment lands
 * cancelled → closed by the platform admin, read-only
 */
class Subscription extends Model
{
    public const TRIALING = 'trialing';

    public const ACTIVE = 'active';

    public const PAST_DUE = 'past_due';

    public const SUSPENDED = 'suspended';

    public const CANCELLED = 'cancelled';

    protected $table = 'subscriptions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'notices' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(BillingCharge::class)->latest('id');
    }

    /** When the current trial or paid period ends. */
    public function endsAt(): ?CarbonInterface
    {
        return $this->status === self::TRIALING
            ? $this->trial_ends_at
            : $this->current_period_ends_at;
    }

    /** Writes are allowed while trialing, active or inside the grace period. */
    public function isWritable(): bool
    {
        return match ($this->status) {
            self::TRIALING, self::ACTIVE => true,
            self::PAST_DUE => $this->grace_ends_at === null || $this->grace_ends_at->isFuture(),
            default => false,
        };
    }

    public function daysLeft(): ?int
    {
        $end = $this->status === self::PAST_DUE ? $this->grace_ends_at : $this->endsAt();

        return $end === null ? null : max(0, (int) ceil(now()->diffInHours($end, false) / 24));
    }

    public function hasNotice(string $key): bool
    {
        return in_array($key, $this->notices ?? [], true);
    }

    public function markNotice(string $key): void
    {
        $this->notices = array_values(array_unique([...($this->notices ?? []), $key]));
        $this->save();
    }
}
