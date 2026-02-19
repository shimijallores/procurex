<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterListItemRequest extends FormRequest
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
            'master_list_category_id' => ['required', 'exists:master_list_categories,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'item_name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'default_unit_price' => ['nullable', 'numeric', 'min:0'],
            'is_phased_out' => ['boolean'],
            'phased_out_reason' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'search_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
