<?php

namespace App\Http\Requests;

use App\Services\SanitizationService;
use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
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

        if ($this->has('name')) {
            $sanitizedData['name'] = $sanitizationService->sanitizeHtml($this->input('name'));
        }

        if ($this->has('subject')) {
            $sanitizedData['subject'] = $sanitizationService->sanitizeHtml($this->input('subject'));
        }

        if ($this->has('message')) {
            $sanitizedData['message'] = $sanitizationService->sanitizeHtml($this->input('message'));
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
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10|max:5000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('ui.contact.validation.name_required'),
            'name.min' => __('ui.contact.validation.name_min'),
            'email.required' => __('ui.contact.validation.email_required'),
            'email.email' => __('ui.contact.validation.email_email'),
            'subject.required' => __('ui.contact.validation.subject_required'),
            'subject.min' => __('ui.contact.validation.subject_min'),
            'message.required' => __('ui.contact.validation.message_required'),
            'message.min' => __('ui.contact.validation.message_min'),
        ];
    }
}
