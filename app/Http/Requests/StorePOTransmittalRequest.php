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
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'coa.transmittal_no' => ['nullable', 'string', 'max:100'],
            'coa.header_text' => ['nullable', 'string'],
            'coa.signatory_name' => ['nullable', 'string', 'max:150'],
            'coa.signatory_title' => ['nullable', 'string', 'max:150'],
            'opg.transmittal_no' => ['nullable', 'string', 'max:100'],
            'opg.header_text' => ['nullable', 'string'],
            'opg.signatory_name' => ['nullable', 'string', 'max:150'],
            'opg.signatory_title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
