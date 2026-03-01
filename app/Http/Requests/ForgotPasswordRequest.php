<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AppliesRequestLocale;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
{
    use AppliesRequestLocale;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->applyRequestLocale();
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'locale' => [
                'nullable',
                'string',
                Rule::in(LocaleResolver::supportedLocales()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('auth_validation.email.required'),
            'email.email' => __('auth_validation.email.email'),
            'email.max' => __('auth_validation.email.max'),
            'locale.in' => __('auth_validation.locale.in'),
        ];
    }
}
