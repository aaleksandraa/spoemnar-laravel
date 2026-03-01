<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        return [
            'role' => ['required', 'string', Rule::in(['user', 'editor', 'admin'])],
            'action' => ['sometimes', 'string', Rule::in(['add', 'remove'])],
        ];
    }

    /**
     * Configure the validator instance to add custom validation logic.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Prevent privilege escalation: users can't assign roles higher than their own
            $requestedRole = $this->input('role');
            $currentUser = $this->user();

            if (!$currentUser) {
                return;
            }

            // Define role hierarchy (higher number = higher privilege)
            $roleHierarchy = [
                'user' => 1,
                'editor' => 2,
                'admin' => 3,
            ];

            $currentUserRole = $currentUser->role ?? 'user';
            $currentUserLevel = $roleHierarchy[$currentUserRole] ?? 0;
            $requestedRoleLevel = $roleHierarchy[$requestedRole] ?? 0;

            // Users cannot assign roles higher than their own level
            // Admins (level 3) can assign admin roles (equal to their level)
            if ($requestedRoleLevel > $currentUserLevel) {
                $validator->errors()->add(
                    'role',
                    'You cannot assign a role with privileges higher than your own.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'The role field is required.',
            'role.in' => 'The role must be user, editor, or admin.',
            'action.in' => 'The action must be either add or remove.',
        ];
    }
}
