<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectCodeRequest extends FormRequest
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
            'office_id' => ['required', 'exists:offices,id'],
            'code' => ['required', 'string', 'max:255', 'unique:project_codes,code'],
            'name' => ['required', 'string', 'max:255', 'unique:project_codes,name'],
        ];
    }
}
