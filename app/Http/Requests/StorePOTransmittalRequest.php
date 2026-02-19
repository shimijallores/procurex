<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePOTransmittalRequest extends FormRequest
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
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'type' => [
                'required',
                Rule::in(['coa', 'opg']),
                Rule::unique('po_transmittals', 'type')->where(
                    fn($query) => $query->where('purchase_order_id', $this->integer('purchase_order_id')),
                ),
            ],
            'transmittal_no' => ['nullable', 'string', 'max:100'],
            'transmittal_date' => ['required', 'date'],
            'header_text' => ['nullable', 'string'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
            'coa_circular_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
