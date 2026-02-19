<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
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
            'emanating_id'          => ['required', 'integer', 'exists:emanatings,id'],
            'office_id'             => ['required', 'integer', 'exists:offices,id'],
            'fund_id'               => ['required', 'integer', 'exists:funds,id'],
            'pr_no'                 => ['nullable', 'string', 'max:50'],
            'pr_date'               => ['nullable', 'date'],
            'sai_no'                => ['nullable', 'string', 'max:50'],
            'sai_date'              => ['nullable', 'date'],
            'purpose'               => ['nullable', 'string'],
            'total_amount'          => ['nullable', 'numeric', 'min:0'],
            'status'                => ['nullable', 'string', 'in:draft,returned,for_budget_review,approved,cancelled'],
            'remarks'               => ['nullable', 'string'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.emanating_item_id' => ['required', 'integer', 'exists:emanating_items,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'     => ['required', 'numeric', 'min:0'],
            'items.*.vat_applicable' => ['boolean'],
            'items.*.vat_rate'      => ['nullable', 'numeric', 'min:0', 'max:1'],
            'items.*.remarks'       => ['nullable', 'string'],
        ];
    }
}
