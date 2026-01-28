<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'office_id' => ['required', 'exists:offices,id'],
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
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'This email address is already taken.',
            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'The selected role is invalid.',
            'office_id.required' => 'Please select an office.',
            'office_id.exists' => 'The selected office is invalid.',
        ];
    }
}
