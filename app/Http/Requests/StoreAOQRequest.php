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
            'aoq_date' => ['required', 'date'],
            'quotations' => ['required', 'array', 'min:1'],
            'quotations.*.supplier_id' => ['required', 'integer', 'distinct', 'exists:suppliers,id'],
            'quotations.*.submitted_at' => ['nullable', 'date'],
            'quotations.*.remarks' => ['nullable', 'string'],
            'quotations.*.unit_prices' => ['required', 'array', 'min:1'],
            'quotations.*.unit_prices.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
