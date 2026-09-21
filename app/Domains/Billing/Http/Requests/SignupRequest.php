<?php

namespace App\Domains\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('billing.signup_enabled');
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            // Angolan NIF: digits and letters, e.g. 5417000000 or 004517823LA040.
            'tax_id' => ['required', 'string', 'regex:/^[0-9A-Za-z]{9,14}$/', Rule::unique('companies', 'tax_id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:/^(\+?244)?\s?9\d{2}\s?\d{3}\s?\d{3}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan' => ['required', 'string', Rule::exists('plans', 'code')->where('is_public', true)],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => __('billing.attributes.company_name'),
            'tax_id' => 'NIF',
        ];
    }
}
