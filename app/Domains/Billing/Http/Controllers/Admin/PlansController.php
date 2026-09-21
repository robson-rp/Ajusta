<?php

namespace App\Domains\Billing\Http\Controllers\Admin;

use App\Domains\Billing\Http\Resources\PlanResource;
use App\Domains\Billing\Models\Plan;
use App\Platform\Http\Controller;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public function index()
    {
        return PlanResource::collection(Plan::orderBy('sort')->get());
    }

    public function update(Request $request, Plan $plan): PlanResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'price_monthly' => ['sometimes', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
            'features' => ['sometimes', 'array'],
            'features.max_users' => ['nullable', 'integer', 'min:1'],
            'features.max_companies' => ['nullable', 'integer', 'min:1'],
            'features.customer_portal' => ['boolean'],
            'features.recurring_invoices' => ['boolean'],
            'features.advanced_reports' => ['boolean'],
        ]);

        if (isset($data['features'])) {
            $data['features'] = array_merge($plan->features ?? [], $data['features']);
        }

        $plan->update($data);

        return new PlanResource($plan);
    }
}
