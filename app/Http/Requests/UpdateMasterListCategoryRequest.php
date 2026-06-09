<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterListCategoryRequest extends FormRequest
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
        $categoryId = $this->route('master_list_category')?->id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:master_list_categories,name,'.$categoryId],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
