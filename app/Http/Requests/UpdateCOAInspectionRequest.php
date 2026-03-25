<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\COAInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCOAInspectionRequest extends FormRequest
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
        /** @var COAInspection $coaInspection */
        $coaInspection = $this->route('coa_inspection');

        return [
            'purchase_order_id' => [
                'required',
                'integer',
                'exists:purchase_orders,id',
                Rule::unique('coa_inspections', 'purchase_order_id')->ignore($coaInspection->id),
            ],
            'svp.header_text' => ['nullable', 'string', 'max:3000'],
            'svp.salutation' => ['nullable', 'string', 'max:255'],
            'bidding.header_text' => ['nullable', 'string', 'max:3000'],
            'bidding.salutation' => ['nullable', 'string', 'max:255'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
