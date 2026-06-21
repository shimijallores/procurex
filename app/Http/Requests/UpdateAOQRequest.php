<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAOQRequest extends FormRequest
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'batch_id.required' => 'Please select or create a batch.',
            'aoq_date.required' => 'The AOQ date is required.',
            'quotations.required' => 'Please add at least one supplier quotation.',
            'quotations.*.supplier_id.required' => 'Please select a supplier.',
            'quotations.*.supplier_id.distinct' => 'Each supplier can only be added once.',
        ];
    }
}
