<?php

namespace App\Http\Requests;

use App\Services\SanitizationService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHeroSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation by sanitizing text fields
     */
    protected function prepareForValidation(): void
    {
        $sanitizationService = app(SanitizationService::class);

        $sanitizedData = [];

        // Sanitize all text fields
        $textFields = [
            'hero_title',
            'hero_subtitle',
            'hero_image_url',
            'cta_button_text',
            'cta_button_link',
            'secondary_button_text',
            'secondary_button_link',
        ];

        foreach ($textFields as $field) {
            if ($this->has($field)) {
                $sanitizedData[$field] = $sanitizationService->sanitizeHtml($this->input($field));
            }
        }

        if (!empty($sanitizedData)) {
            $this->merge($sanitizedData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'hero_image_url' => ['nullable', 'url', 'max:255', new \App\Rules\WhitelistedUrlRule()],
            'cta_button_text' => ['required', 'string', 'max:100'],
            'cta_button_link' => ['required', 'string', 'max:255'],
            'secondary_button_text' => ['required', 'string', 'max:100'],
            'secondary_button_link' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate button links - they can be relative paths or whitelisted URLs
            $this->validateButtonLink('cta_button_link', $validator);
            $this->validateButtonLink('secondary_button_link', $validator);
        });
    }

    /**
     * Validate a button link field.
     * Allows relative paths (starting with /) or whitelisted HTTPS URLs.
     *
     * @param string $field
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateButtonLink(string $field, $validator): void
    {
        $value = $this->input($field);

        if (empty($value)) {
            return;
        }

        // Allow relative paths starting with /
        if (str_starts_with($value, '/')) {
            // Validate relative path format
            if (!preg_match('/^\/[a-zA-Z0-9\/_-]*$/', $value)) {
                $validator->errors()->add($field, "The {$field} must be a valid relative path or URL.");
            }
            return;
        }

        // Allow anchor links starting with #
        if (str_starts_with($value, '#')) {
            // Validate anchor format
            if (!preg_match('/^#[a-zA-Z0-9_-]*$/', $value)) {
                $validator->errors()->add($field, "The {$field} must be a valid anchor link.");
            }
            return;
        }

        // If it's not a relative path or anchor, it must be a full URL
        // Validate it using WhitelistedUrlRule
        $rule = new \App\Rules\WhitelistedUrlRule();
        $rule->validate($field, $value, function ($message) use ($validator, $field) {
            $validator->errors()->add($field, $message);
        });
    }
}
