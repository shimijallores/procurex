<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'noas' => ['required', 'array', 'min:1'],
            'noas.*.noa_id' => ['required', 'integer', 'exists:noas,id'],
            'noas.*.po_no' => ['nullable', 'string', 'max:255'],
            'noas.*.po_date' => ['required', 'date'],
            'noas.*.mode_of_procurement' => ['required', 'string', 'max:120', Rule::in(['Small Value', 'Direct Contracting', 'Direct Acquisition'])],
            'noas.*.delivery_term_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'noas.*.payment_term' => ['nullable', 'string', 'max:255'],
            'noas.*.place_of_delivery' => ['nullable', 'string', 'max:255'],
            'noas.*.remarks' => ['nullable', 'string'],
        ];
    }
}
