<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AcceptanceInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcceptanceInspectionRequest extends FormRequest
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
        /** @var AcceptanceInspection $acceptanceInspection */
        $acceptanceInspection = $this->route('acceptance_inspection');

        return [
            'purchase_order_id' => [
                'required',
                'integer',
                'exists:purchase_orders,id',
                Rule::unique('acceptance_inspections', 'purchase_order_id')->ignore($acceptanceInspection->id),
            ],
            'air_no' => ['nullable', 'string', 'max:100'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'acceptance_date_received' => ['nullable', 'date'],
            'acceptance_status' => ['nullable', Rule::in(['complete', 'partial'])],
            'inspection_date_inspected' => ['nullable', 'date'],
            'inspection_findings_text' => ['nullable', 'string', 'max:500'],
            'inspection_status_ok' => ['nullable', 'boolean'],
            'property_officer_name' => ['nullable', 'string', 'max:150'],
            'property_officer_title' => ['nullable', 'string', 'max:150'],
            'inspection_officer_name' => ['nullable', 'string', 'max:150'],
            'inspection_officer_title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
