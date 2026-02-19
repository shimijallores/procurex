<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRFQRequest extends FormRequest
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
            'pr_id' => ['required', 'integer', 'exists:purchase_requests,id'],
            'rfq_date' => ['required', 'date'],
            'submission_deadline' => ['nullable', 'date', 'after_or_equal:rfq_date'],
            'remarks' => ['nullable', 'string'],
            'supplier_ids' => ['required', 'array', 'min:1'],
            'supplier_ids.*' => ['required', 'integer', 'distinct', 'exists:suppliers,id'],
        ];
    }
}
