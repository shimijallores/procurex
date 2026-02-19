<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\POTransmittal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePOTransmittalRequest extends FormRequest
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
        /** @var POTransmittal $poTransmittal */
        $poTransmittal = $this->route('po_transmittal');

        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'type' => ['required', Rule::in(['coa', 'opg'])],
            'transmittal_no' => ['nullable', 'string', 'max:100'],
            'transmittal_date' => ['required', 'date'],
            'header_text' => ['nullable', 'string'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
            'coa_circular_no' => ['nullable', 'string', 'max:100'],
            Rule::unique('po_transmittals', 'type')
                ->ignore($poTransmittal->id)
                ->where(
                    fn($query) => $query->where('purchase_order_id', $this->integer('purchase_order_id')),
                ),
        ];
    }
}
