<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
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
            'po_date' => ['required', 'date'],
            'mode_of_procurement' => ['required', 'string', 'max:120', Rule::in(['Small Value', 'Direct Contracting', 'Direct Acquisition'])],
            'place_of_delivery' => ['nullable', 'string', 'max:255'],
            'delivery_term_days' => ['nullable', 'integer', 'between:1,30'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.rfq_item_id' => ['required', 'integer', 'exists:rfq_items,id'],
            'items.*.quantity_snapshot' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost_snapshot' => ['required', 'numeric', 'min:0'],
        ];
    }
}
