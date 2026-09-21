<?php

namespace App\Domains\Billing\Http\Requests;

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
        return [
            'plan' => ['required', 'string', Rule::exists('plans', 'code')->where('is_public', true)],
            'months' => ['required', 'integer', Rule::in(config('billing.periods'))],
            'method' => ['required', 'string'],
            'phone' => [
                Rule::requiredIf(fn () => $this->input('method') === 'gpo'),
                'nullable',
                'string',
                'regex:/^(\+?244)?\s?9\d{2}\s?\d{3}\s?\d{3}$/',
            ],
        ];
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
