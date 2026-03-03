<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
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
            'noa_id' => ['required', 'integer', 'exists:noas,id', 'unique:purchase_orders,noa_id'],
            'po_no' => ['nullable', 'string', 'max:50', 'regex:/^\d{4}-\d{4}$/'],
            'po_date' => ['required', 'date'],
            'mode_of_procurement' => ['required', 'string', 'max:120'],
            'place_of_delivery' => ['required', 'string', 'max:255'],
            'delivery_term_days' => ['nullable', 'integer', 'in:15,30'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'total_amount_words' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.rfq_item_id' => ['required', 'integer', 'exists:rfq_items,id'],
            'items.*.quantity_snapshot' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost_snapshot' => ['required', 'numeric', 'min:0'],
            'items.*.amount_snapshot' => ['required', 'numeric', 'min:0'],
        ];
    }
}
