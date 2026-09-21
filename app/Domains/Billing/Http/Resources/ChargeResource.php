<?php

namespace App\Domains\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'plan' => $this->whenLoaded('plan', fn () => ['code' => $this->plan->code, 'name' => $this->plan->name]),
            'months' => $this->months,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway' => $this->gateway,
            'method' => $this->method,
            'status' => $this->status,
            'message' => $this->status === 'failed' ? $this->provider_message : null,
            'reference_number' => $this->reference_number,
            'entity_number' => $this->entity_number,
            'customer_phone' => $this->customer_phone,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
