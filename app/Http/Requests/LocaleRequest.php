<?php

namespace App\Http\Requests;

use App\Support\LocaleResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LocaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supportedLocales = LocaleResolver::supportedLocales();

        return [
            'locale' => [
                'required',
                'string',
                'in:' . implode(',', $supportedLocales),
                'max:10',
                'regex:/^[a-z]{2}$/',
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
            'locale.required' => 'Locale parameter is required.',
            'locale.in' => 'The selected locale is not supported.',
            'locale.max' => 'Locale parameter is too long.',
            'locale.regex' => 'Locale must be a valid two-letter language code.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize the locale parameter before validation
        if ($this->route('locale')) {
            $localeValue = $this->route('locale');

            // Check for null bytes and control characters before normalization
            // This prevents attacks like "en\x00" from being normalized to "en"
            if (is_string($localeValue) && preg_match('/[\x00-\x1F\x7F]/', $localeValue)) {
                // Set an invalid value that will fail validation
                $this->merge(['locale' => 'INVALID_CONTROL_CHARS']);
                return;
            }

            $this->merge([
                'locale' => LocaleResolver::normalizeLocale($localeValue),
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * For locale validation, we want to return 404 instead of validation errors
     * to prevent information disclosure about supported locales.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new NotFoundHttpException('Locale not found.');
    }
}
