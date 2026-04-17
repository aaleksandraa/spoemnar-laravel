<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AppliesRequestLocale;
use App\Services\Security\SecurityLogger;
use App\Support\DisposableEmailDomainChecker;
use App\Support\LocaleResolver;
use App\Support\PasswordPolicy;
use App\Support\RegistrationFormProtection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
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
            'company' => ['nullable', 'size:0'],
            'form_rendered_at' => ['required', 'integer'],
            'form_signature' => ['required', 'string', 'size:64'],
            'cf-turnstile-response' => ['nullable', 'string'],
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
            'company.size' => __('auth_validation.security.bot_detected'),
            'form_rendered_at.required' => __('auth_validation.security.invalid_form'),
            'form_rendered_at.integer' => __('auth_validation.security.invalid_form'),
            'form_signature.required' => __('auth_validation.security.invalid_form'),
            'form_signature.size' => __('auth_validation.security.invalid_form'),
            'locale.in' => __('auth_validation.locale.in'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $suspiciousReasons = [];
            $normalizedEmail = mb_strtolower(trim((string) $this->input('email', '')), 'UTF-8');
            $emailDomain = DisposableEmailDomainChecker::extractDomain($normalizedEmail);

            if (filled($this->input('company'))) {
                $validator->errors()->add('company', __('auth_validation.security.bot_detected'));
                $suspiciousReasons[] = 'honeypot_filled';
            }

            if ($normalizedEmail !== '' && DisposableEmailDomainChecker::isDisposableEmail($normalizedEmail)) {
                $validator->errors()->add('email', __('auth_validation.security.disposable_email'));
                $suspiciousReasons[] = 'disposable_email_domain';
            }

            $formRenderedAt = (int) $this->input('form_rendered_at', 0);
            $secondsSinceFormRender = now()->timestamp - $formRenderedAt;

            if ($formRenderedAt <= 0 || $secondsSinceFormRender > 7200) {
                $validator->errors()->add('form_rendered_at', __('auth_validation.security.expired'));
                $suspiciousReasons[] = 'expired_form';
                $this->logSuspiciousRegistration($normalizedEmail, $emailDomain, $suspiciousReasons);
                return;
            }

            if ($secondsSinceFormRender < 3) {
                $validator->errors()->add('form_rendered_at', __('auth_validation.security.too_fast'));
                $suspiciousReasons[] = 'submitted_too_fast';
            }

            if (!RegistrationFormProtection::hasValidSignature(
                $formRenderedAt,
                (string) $this->input('form_signature', ''),
                (string) $this->ip()
            )) {
                $validator->errors()->add('form_signature', __('auth_validation.security.invalid_signature'));
                $suspiciousReasons[] = 'invalid_signature';
            }

            $turnstileSiteKey = trim((string) config('services.turnstile.site_key', ''));
            $turnstileSecretKey = trim((string) config('services.turnstile.secret_key', ''));

            if ($turnstileSiteKey === '' || $turnstileSecretKey === '') {
                $this->logSuspiciousRegistration($normalizedEmail, $emailDomain, $suspiciousReasons);
                return;
            }

            $turnstileToken = trim((string) $this->input('cf-turnstile-response', ''));
            if ($turnstileToken === '') {
                $validator->errors()->add('cf-turnstile-response', __('auth_validation.security.captcha_required'));
                $suspiciousReasons[] = 'captcha_required';
                $this->logSuspiciousRegistration($normalizedEmail, $emailDomain, $suspiciousReasons);
                return;
            }

            try {
                $turnstileVerifyResponse = Http::asForm()
                    ->timeout(8)
                    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => $turnstileSecretKey,
                        'response' => $turnstileToken,
                        'remoteip' => $this->ip(),
                    ]);

                if (!$turnstileVerifyResponse->ok() || !$turnstileVerifyResponse->json('success')) {
                    $validator->errors()->add('cf-turnstile-response', __('auth_validation.security.captcha_failed'));
                    $suspiciousReasons[] = 'captcha_failed';
                }
            } catch (\Throwable $exception) {
                $validator->errors()->add('cf-turnstile-response', __('auth_validation.security.captcha_unavailable'));
                $suspiciousReasons[] = 'captcha_unavailable';
            }

            $this->logSuspiciousRegistration($normalizedEmail, $emailDomain, $suspiciousReasons);
        });
    }

    /**
     * @param array<int, string> $reasons
     */
    private function logSuspiciousRegistration(string $email, ?string $emailDomain, array $reasons): void
    {
        if ($reasons === []) {
            return;
        }

        $primaryReason = $reasons[0];

        app(SecurityLogger::class)->logSuspiciousRegistration(
            $this,
            $email !== '' ? $email : null,
            $primaryReason,
            [
                'email_domain' => $emailDomain,
                'reasons' => $reasons,
            ]
        );
    }
}
