<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
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
        $editingUserId = $this->route('user')?->id;
        $roleIds = array_map('intval', (array) $this->input('role_ids', []));
        $hasOfficeRole = Role::query()
            ->whereIn('id', $roleIds)
            ->where('is_system_role', false)
            ->exists();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$editingUserId],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'office_id' => $hasOfficeRole ? ['required', 'exists:offices,id'] : ['nullable'],
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
            'role_ids.required' => 'Please select at least one role.',
            'role_ids.array' => 'Roles must be sent as a list.',
            'role_ids.min' => 'Please select at least one role.',
            'role_ids.*.exists' => 'One or more selected roles are invalid.',
            'office_id.required' => 'Please select an office for office roles.',
            'office_id.exists' => 'The selected office is invalid.',
        ];
    }
}
