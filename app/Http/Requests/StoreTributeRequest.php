<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTributeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint - no authentication required
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => 'required|string|max:255',
            'author_email' => 'required|email|max:255',
            'message' => 'required|string|max:1000',
            'honeypot' => 'size:0', // Must be empty if present (bot trap)
            'timestamp' => 'required|integer|min:' . (time() - 3600) . '|max:' . time(), // Max 1 hour old, not in future
        ];
    }
}

