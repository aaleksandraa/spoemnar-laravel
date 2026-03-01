<?php

namespace App\Http\Requests;

use App\Services\SanitizationService;
use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller
        return true;
    }

    /**
     * Prepare the data for validation by sanitizing HTML content
     */
    protected function prepareForValidation(): void
    {
        $sanitizationService = app(SanitizationService::class);

        if ($this->has('caption')) {
            $this->merge([
                'caption' => $sanitizationService->sanitizeHtml($this->input('caption')),
            ]);
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
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:5120', // 5MB max file size
            ],
            'caption' => [
                'nullable',
                'string',
                'max:500',
            ],
            'display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
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
            'image.required' => 'An image file is required.',
            'image.image' => 'The file must be a valid image.',
            'image.mimes' => 'The image must be a file of type: jpg, jpeg, png, gif, webp.',
            'image.max' => 'The image must not exceed 5MB in size.',
            'caption.max' => 'The caption may not be longer than 500 characters.',
            'display_order.integer' => 'The display order must be an integer.',
            'display_order.min' => 'The display order must be at least 0.',
        ];
    }
}
