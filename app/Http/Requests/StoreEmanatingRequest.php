<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmanatingRequest extends FormRequest
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
            'ppmp_id' => ['required', 'exists:ppmps,id'],
            'ppmp_category_id' => ['required', 'exists:ppmp_categories,id'],
            'pr_no' => ['nullable', 'string', 'max:50'],
            'is_addendum' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'reimbursement' => ['nullable', 'boolean'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
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
            'ppmp_id.required' => 'Please select a PPMP.',
            'ppmp_id.exists' => 'The selected PPMP does not exist.',
            'ppmp_category_id.required' => 'Please select a PPMP Category.',
            'ppmp_category_id.exists' => 'The selected PPMP category does not exist.',
            'csv_file.required' => 'A CSV file is required to create an emanating request.',
            'csv_file.mimes' => 'The CSV file must be in CSV, TXT, XLSX, or XLS format.',
            'csv_file.max' => 'The CSV file must not exceed 10MB.',
        ];
    }
}
