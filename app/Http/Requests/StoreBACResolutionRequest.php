<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBACResolutionRequest extends FormRequest
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
            'aoq_id' => ['required', 'integer', 'exists:aoqs,id', 'unique:bac_resolutions,aoq_id'],
            'resolution_date' => ['required', 'date'],
            'meeting_date' => ['nullable', 'date'],
            'project_name' => ['required', 'string', 'max:255'],
            'winner_supplier_name' => ['required', 'string', 'max:255'],
            'winner_amount' => ['required', 'numeric', 'min:0'],
            'calculation_label' => ['required', 'string', 'max:100'],
            'justification' => ['nullable', 'string'],
            'signatory_chairperson' => ['nullable', 'string', 'max:255'],
            'signatory_member_one' => ['nullable', 'string', 'max:255'],
            'signatory_member_two' => ['nullable', 'string', 'max:255'],
            'signatory_member_three' => ['nullable', 'string', 'max:255'],
        ];
    }
}
