<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmanatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ppmp_id' => ['nullable', 'exists:ppmps,id'],
            'ppmp_category_id' => ['nullable', 'exists:ppmp_categories,id'],
            'pr_no' => ['nullable', 'string', 'max:50'],
            'is_addendum' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'reimbursement' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ppmp_id.exists' => 'The selected PPMP does not exist.',
            'ppmp_category_id.exists' => 'The selected PPMP category does not exist.',
        ];
    }
}
