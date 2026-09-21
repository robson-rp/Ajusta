<?php

namespace App\Domains\Billing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subscription plan. Prices are monthly, in cents of Kwanza; `features`
 * holds the limits and switches checked by PlanFeatures (a null limit means
 * unlimited).
 */
class Plan extends Model
{
    protected $table = 'plans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_public' => 'boolean',
            'price_monthly' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->orderBy('sort');
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        return ($this->features ?? [])[$key] ?? $default;
    }
}
