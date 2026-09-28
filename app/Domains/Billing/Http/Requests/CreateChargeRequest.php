<?php

namespace App\Domains\Billing\Http\Requests;

use App\Domains\Billing\Gateways\GatewayManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $methods = $this->availableMethods();

        return [
            'plan' => ['required', 'string', Rule::exists('plans', 'code')->where('is_public', true)],
            'months' => ['required', 'integer', Rule::in(config('billing.periods'))],
            'method' => ['required', 'string', Rule::in($methods)],
            'phone' => [
                Rule::requiredIf(fn () => $this->input('method') === 'gpo' && in_array('gpo', $methods, true)),
                'nullable',
                'string',
                'regex:/^(\+?244)?\s?9\d{2}\s?\d{3}\s?\d{3}$/',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function availableMethods(): array
    {
        return array_column(app(GatewayManager::class)->options(), 'method');
    }

    /** Phone as AppyPay expects it: 9 digits, no country code. */
    public function normalizedPhone(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->input('phone'));

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '244') ? substr($digits, 3) : $digits;
    }
}
