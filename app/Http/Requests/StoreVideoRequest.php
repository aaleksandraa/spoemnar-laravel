<?php

namespace App\Http\Requests;

use App\Rules\VideoUrlRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'youtube_url' => [
                'required',
                'string',
                'url',
                new VideoUrlRule(),
            ],
            'title' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}

