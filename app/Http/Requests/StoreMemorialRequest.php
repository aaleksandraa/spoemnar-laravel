<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemorialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'date_format:Y-m-d'],
            'death_date' => ['required', 'date', 'date_format:Y-m-d', 'after:birth_date'],
            'birth_country_id' => ['nullable', 'integer', 'exists:countries,id', 'required_with:birth_place_id'],
            'birth_place_id' => [
                'nullable',
                'integer',
                Rule::exists('places', 'id')->where(function ($query) {
                    $countryId = $this->input('birth_country_id');
                    if (is_numeric($countryId)) {
                        $query->where('country_id', (int) $countryId);
                    }
                }),
            ],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'death_country_id' => ['nullable', 'integer', 'exists:countries,id', 'required_with:death_place_id'],
            'death_place_id' => [
                'nullable',
                'integer',
                Rule::exists('places', 'id')->where(function ($query) {
                    $countryId = $this->input('death_country_id');
                    if (is_numeric($countryId)) {
                        $query->where('country_id', (int) $countryId);
                    }
                }),
            ],
            'death_place' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'profile_image_url' => ['nullable', 'string', 'max:2048'],
            'is_public' => ['nullable', 'boolean'],
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
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'birth_date.required' => 'Birth date is required',
            'birth_date.date_format' => 'Birth date must be in DD.MM.YYYY format',
            'death_date.required' => 'Death date is required',
            'death_date.date_format' => 'Death date must be in DD.MM.YYYY format',
            'death_date.after' => 'Death date must be after birth date',
        ];
    }

    protected function prepareForValidation(): void
    {
        $birthDate = $this->normalizeDateInput($this->input('birth_date'));
        $deathDate = $this->normalizeDateInput($this->input('death_date'));

        $this->merge([
            'birth_date' => $birthDate ?? $this->input('birth_date'),
            'death_date' => $deathDate ?? $this->input('death_date'),
            'birth_country_id' => $this->normalizeInteger($this->input('birth_country_id')),
            'birth_place_id' => $this->normalizeInteger($this->input('birth_place_id')),
            'death_country_id' => $this->normalizeInteger($this->input('death_country_id')),
            'death_place_id' => $this->normalizeInteger($this->input('death_place_id')),
        ]);
    }

    private function normalizeDateInput(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) === 1) {
                return Carbon::createFromFormat('Y-m-d', $trimmed)->format('Y-m-d');
            }

            if (preg_match('/^\d{2}\.\d{2}\.\d{4}\.?$/', $trimmed) === 1) {
                $normalized = rtrim($trimmed, '.');
                return Carbon::createFromFormat('d.m.Y', $normalized)->format('Y-m-d');
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
