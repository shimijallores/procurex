<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'proprietor' => ['nullable', 'string', 'max:255'],
            'authorized_representative' => ['nullable', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tin' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
