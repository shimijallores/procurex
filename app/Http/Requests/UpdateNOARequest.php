<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNOARequest extends FormRequest
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
            'noa_date' => ['required', 'date'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
