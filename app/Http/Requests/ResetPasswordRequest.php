<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AppliesRequestLocale;
use App\Support\LocaleResolver;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetPasswordRequest extends FormRequest
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
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => PasswordPolicy::validationRules(),
            'password_confirmation' => ['required', 'same:password'],
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
            'token.required' => __('auth_validation.token.required'),
            'email.required' => __('auth_validation.email.required'),
            'email.email' => __('auth_validation.email.email'),
            'email.max' => __('auth_validation.email.max'),
            'password.required' => __('auth_validation.password.required'),
            'password.min' => __('auth_validation.password.min'),
            'password.regex' => __('auth_validation.password.regex'),
            'password_confirmation.required' => __('auth_validation.password_confirmation.required'),
            'password_confirmation.same' => __('auth_validation.password_confirmation.same'),
            'locale.in' => __('auth_validation.locale.in'),
        ];
    }
}
