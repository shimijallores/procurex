<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'purchase_orders' => ['required', 'array', 'min:1'],
            'purchase_orders.*.id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'purchase_orders.*.coa.transmittal_no' => ['nullable', 'string', 'max:100'],
            'purchase_orders.*.coa.header_text' => ['nullable', 'string'],
            'purchase_orders.*.coa.signatory_name' => ['nullable', 'string', 'max:150'],
            'purchase_orders.*.coa.signatory_title' => ['nullable', 'string', 'max:150'],
            'purchase_orders.*.opg.transmittal_no' => ['nullable', 'string', 'max:100'],
            'purchase_orders.*.opg.header_text' => ['nullable', 'string'],
            'purchase_orders.*.opg.signatory_name' => ['nullable', 'string', 'max:150'],
            'purchase_orders.*.opg.signatory_title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
