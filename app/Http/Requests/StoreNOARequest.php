<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNOARequest extends FormRequest
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
            'bac_resolution_id' => ['required', 'integer', 'exists:bac_resolutions,id', 'unique:noas,bac_resolution_id'],
            'noa_no' => ['required', 'string', 'max:255', 'unique:noas,noa_no'],
            'noa_date' => ['required', 'date'],
        ];
    }
}
