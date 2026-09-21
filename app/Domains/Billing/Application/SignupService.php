<?php

namespace App\Domains\Billing\Application;

use App\Domains\Accounts\Application\CompanyService;
use App\Domains\Accounts\Contracts\CompanyAddressWriter;
use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Billing\Mail\WelcomeMail;
use App\Domains\Billing\Models\Plan;
use App\Domains\Billing\Models\Subscription;
use App\Domains\Contacts\Models\Country;
use App\Facades\Hashids;
use App\Support\Hashids\HashidConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Silber\Bouncer\BouncerFacade;
use Throwable;

/**
 * Self-service signup: one account owner, one company (one NIF) and a free
 * trial of the chosen plan. Same company setup as CompaniesController::store.
 */
class SignupService
{
    public function __construct(
        private readonly CompanyService $companyService,
        private readonly CompanyAddressWriter $addressWriter,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @param  array{company_name: string, tax_id: string, name: string, email: string, phone: string, password: string, plan: string}  $data
     * @return array{user: User, company: Company, subscription: Subscription}
     */
    public function register(array $data): array
    {
        $result = DB::transaction(function () use ($data) {
            $plan = Plan::where('code', $data['plan'])->firstOrFail();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
            ]);
            $user->setSettings(['language' => 'default']);

            $company = Company::create([
                'name' => $data['company_name'],
                'tax_id' => $data['tax_id'],
                'owner_id' => $user->id,
                'slug' => Str::slug($data['company_name']),
            ]);
            $company->unique_hash = Hashids::connection(HashidConnection::Company->value)->encode($company->id);
            $company->save();

            $this->companyService->setupDefaults($company);
            $user->companies()->attach($company->id);

            BouncerFacade::scope()->to($company->id);
            $user->assign('owner');

            $this->addressWriter->upsert($company, [
                'name' => $data['company_name'],
                'phone' => $data['phone'],
                'country_id' => Country::where('code', 'AO')->value('id'),
            ]);

            $subscription = $this->subscriptions->startTrial($user, $plan);

            return compact('user', 'company', 'subscription');
        });

        try {
            Mail::to($result['user']->email)->send(new WelcomeMail($result['subscription']->load('plan'), $result['company']));
        } catch (Throwable $e) {
            Log::warning('Welcome mail not sent', ['user' => $result['user']->id, 'error' => $e->getMessage()]);
        }

        return $result;
    }
}
