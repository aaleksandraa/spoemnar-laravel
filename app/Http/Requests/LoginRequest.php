<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AppliesRequestLocale;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'password' => 'required|string',
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
            'email.max' => __('auth_validation.email.max'),
            'password.required' => __('auth_validation.password.required'),
        ];
    }
}
