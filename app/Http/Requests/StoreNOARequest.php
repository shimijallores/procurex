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
        return [
            'bac_resolution_id' => ['required', 'integer', 'exists:bac_resolutions,id'],
            'selected_aoq_id' => ['required', 'integer', 'exists:aoqs,id', 'unique:noas,aoq_id'],
            'noa_no' => ['nullable', 'string', 'max:255'],
            'noa_date' => ['required', 'date'],
            'calculation_label' => ['nullable', 'string', 'max:100', Rule::in(['Lowest Calculated', 'Single Calculated'])],
            'winner_supplier_name' => ['required', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
