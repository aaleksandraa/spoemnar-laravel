<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AppliesRequestLocale;
use App\Support\LocaleResolver;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    use AppliesRequestLocale;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->applyRequestLocale();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email|max:255',
            'password' => PasswordPolicy::validationRules(),
            'password_confirmation' => ['required', 'same:password'],
            'full_name' => 'nullable|string|max:255',
            'locale' => [
                'nullable',
                'string',
                Rule::in(LocaleResolver::supportedLocales()),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('auth_validation.email.required'),
            'email.email' => __('auth_validation.email.email'),
            'email.unique' => __('auth_validation.email.unique'),
            'email.max' => __('auth_validation.email.max'),
            'password.required' => __('auth_validation.password.required'),
            'password.min' => __('auth_validation.password.min'),
            'password.regex' => __('auth_validation.password.regex'),
            'password_confirmation.required' => __('auth_validation.password_confirmation.required'),
            'password_confirmation.same' => __('auth_validation.password_confirmation.same'),
            'full_name.string' => __('auth_validation.full_name.string'),
            'full_name.max' => __('auth_validation.full_name.max'),
            'locale.in' => __('auth_validation.locale.in'),
        ];
    }
}
