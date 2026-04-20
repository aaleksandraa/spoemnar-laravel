<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AppliesRequestLocale;
use App\Services\SanitizationService;
use App\Support\LocaleResolver;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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

        $sanitizationService = app(SanitizationService::class);
        $prepared = [];

        if ($this->has('email')) {
            $prepared['email'] = mb_strtolower(trim((string) $this->input('email')));
        }

        if ($this->has('full_name')) {
            $value = trim((string) $sanitizationService->sanitizeHtml((string) $this->input('full_name')));
            $prepared['full_name'] = $value !== '' ? $value : null;
        }

        if ($this->has('preferred_locale')) {
            $prepared['preferred_locale'] = LocaleResolver::normalizeLocale((string) $this->input('preferred_locale'));
        }

        if ($prepared !== []) {
            $this->merge($prepared);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $passwordRules = array_values(array_filter(
            PasswordPolicy::validationRules(),
            static fn (string $rule): bool => $rule !== 'required'
        ));

        return [
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'preferred_locale' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(LocaleResolver::supportedLocales()),
            ],
            'current_password' => ['nullable', 'string'],
            'new_password' => array_merge(['nullable', 'confirmed'], $passwordRules),
            'locale' => [
                'nullable',
                'string',
                Rule::in(LocaleResolver::supportedLocales()),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            if (!$user) {
                return;
            }

            $incomingEmail = $this->has('email')
                ? mb_strtolower(trim((string) $this->input('email')))
                : mb_strtolower(trim((string) $user->email));
            $currentEmail = mb_strtolower(trim((string) $user->email));

            $emailChanged = $this->has('email') && $incomingEmail !== '' && $incomingEmail !== $currentEmail;
            $passwordChanged = filled($this->input('new_password'));

            if (!$emailChanged && !$passwordChanged) {
                return;
            }

            $currentPassword = (string) ($this->input('current_password') ?? '');
            if ($currentPassword === '') {
                $validator->errors()->add('current_password', __('auth_validation.account.current_password_required'));
                return;
            }

            if (!Hash::check($currentPassword, (string) $user->password)) {
                $validator->errors()->add('current_password', __('auth_validation.account.current_password_invalid'));
            }

            if ($passwordChanged && hash_equals($currentPassword, (string) $this->input('new_password'))) {
                $validator->errors()->add('new_password', __('auth_validation.account.new_password_same'));
            }
        });
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
            'full_name.string' => __('auth_validation.full_name.string'),
            'full_name.max' => __('auth_validation.full_name.max'),
            'preferred_locale.in' => __('auth_validation.locale.in'),
            'new_password.confirmed' => __('auth_validation.password_confirmation.same'),
            'new_password.min' => __('auth_validation.password.min'),
            'new_password.regex' => __('auth_validation.password.regex'),
            'locale.in' => __('auth_validation.locale.in'),
        ];
    }
}
