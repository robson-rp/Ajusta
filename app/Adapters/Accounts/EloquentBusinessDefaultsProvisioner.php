<?php

namespace App\Adapters\Accounts;

use App\Domains\Accounts\Contracts\CompanyDefaultsProvisioner;
use App\Domains\Accounts\Models\Company;
use App\Domains\Catalog\Models\Unit;
use App\Domains\Receivables\Models\PaymentMethod;

class EloquentBusinessDefaultsProvisioner implements CompanyDefaultsProvisioner
{
    public function provision(Company $company): void
    {
        // Named in the install's default language (pt_AO for AJUSTA).
        $language = config('invoiceshelf.company_defaults.language');

        foreach (['Cash', 'Check', 'Credit Card', 'Bank Transfer'] as $name) {
            PaymentMethod::create([
                'name' => __($name, [], $language),
                'company_id' => $company->id,
            ]);
        }

        foreach (['box', 'cm', 'dz', 'ft', 'g', 'in', 'kg', 'km', 'lb', 'mg', 'pc'] as $name) {
            Unit::create([
                'name' => $name,
                'company_id' => $company->id,
            ]);
        }
    }
}
