<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAOQRequest extends FormRequest
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
            'rfq_id' => ['required', 'integer', 'exists:rfqs,id', 'unique:aoqs,rfq_id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'aoq_date' => ['required', 'date'],
            'quotations' => ['required', 'array', 'min:1'],
            'quotations.*.supplier_id' => ['required', 'integer', 'distinct', 'exists:suppliers,id'],
            'quotations.*.submitted_at' => ['nullable', 'date'],
            'quotations.*.remarks' => ['nullable', 'string'],
            'quotations.*.unit_prices' => ['required', 'array', 'min:1'],
            'quotations.*.unit_prices.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'rfq_id.required' => 'Please select an RFQ.',
            'rfq_id.unique' => 'An AOQ already exists for this RFQ.',
            'batch_id.required' => 'Please select or create a batch.',
            'aoq_date.required' => 'The AOQ date is required.',
            'quotations.required' => 'Please add at least one supplier quotation.',
            'quotations.*.supplier_id.required' => 'Please select a supplier.',
            'quotations.*.supplier_id.distinct' => 'Each supplier can only be added once.',
        ];
    }
}
