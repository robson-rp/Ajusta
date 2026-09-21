<?php

namespace App\Domains\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'price_monthly' => $this->price_monthly,
            'features' => $this->features ?? [],
            'is_public' => $this->is_public,
            'sort' => $this->sort,
        ];
    }
}
