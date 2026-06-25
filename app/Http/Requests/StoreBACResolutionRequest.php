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
        $draft = $this->boolean('save_draft');

        return [
            'batch_id' => $draft ? ['nullable', 'integer', 'exists:batches,id'] : ['required', 'integer', 'exists:batches,id'],
            'resolution_date' => $draft ? ['nullable', 'date'] : ['required', 'date'],
            'meeting_date' => ['nullable', 'date'],
            'project_name' => $draft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'winner_supplier_name' => $draft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'winner_amount' => $draft ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'calculation_label' => $draft ? ['nullable', 'string', 'max:100'] : ['required', 'string', 'max:100'],
            'justification' => ['nullable', 'string'],
            'signatory_chairperson' => ['nullable', 'string', 'max:255'],
            'signatory_member_one' => ['nullable', 'string', 'max:255'],
            'signatory_member_two' => ['nullable', 'string', 'max:255'],
            'signatory_member_three' => ['nullable', 'string', 'max:255'],
            'save_draft' => ['nullable', 'boolean'],
        ];
    }
}
