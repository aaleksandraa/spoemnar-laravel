<?php

namespace App\Http\Requests;

use App\Services\SanitizationService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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

        if ($this->has('email')) {
            $sanitizedData['email'] = $sanitizationService->sanitizeHtml($this->input('email'));
        }

        if ($this->has('full_name')) {
            $sanitizedData['full_name'] = $sanitizationService->sanitizeHtml($this->input('full_name'));
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
            'email' => 'sometimes|email|max:255',
            'full_name' => 'sometimes|string|max:255',
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
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'full_name.string' => 'Full name must be a string.',
            'full_name.max' => 'Full name must not exceed 255 characters.',
        ];
    }
}
