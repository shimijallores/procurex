<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeRequest extends FormRequest
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
        $office = $this->route('office');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('offices', 'name')->ignore($office)],
            'code' => ['required', 'string', 'max:255', Rule::unique('offices', 'code')->ignore($office)],
            'acronym' => ['nullable', 'string', 'max:255'],
        ];
    }
}
