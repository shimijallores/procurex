<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $resolutionId = (int) $this->input('bac_resolution_id');

        return [
            'bac_resolution_id' => ['required', 'integer', 'exists:bac_resolutions,id', 'unique:noas,bac_resolution_id'],
            'noa_no' => ['nullable', 'string', 'max:255'],
            'noa_date' => ['required', 'date', 'after_or_equal:resolution_date'],
            'resolution_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bac_resolutions', 'resolution_no')->ignore($resolutionId),
            ],
            'resolution_date' => ['required', 'date'],
            'calculation_label' => ['nullable', 'string', 'max:255'],
            'winner_supplier_name' => ['required', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
