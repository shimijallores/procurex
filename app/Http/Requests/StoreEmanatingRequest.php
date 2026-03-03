<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'office_id' => ['required', 'exists:offices,id'],
            'ppmp_id' => [
                'required',
                Rule::exists('ppmps', 'id')->where(fn($query) => $query->where('office_id', $this->office_id)),
            ],
            'ppmp_category_id' => ['required', 'exists:ppmp_categories,id'],
            'pr_no' => ['nullable', 'string', 'max:50'],
            'is_addendum' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'reimbursement' => ['nullable', 'boolean'],
            'xlsx_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->ppmp_id || ! $this->ppmp_category_id) {
                return;
            }

            $categoryBelongsToPpmp = \App\Models\PPMPCategory::query()
                ->where('id', $this->ppmp_category_id)
                ->where('ppmp_id', $this->ppmp_id)
                ->exists();

            if (! $categoryBelongsToPpmp) {
                $validator->errors()->add('ppmp_category_id', 'The selected PPMP category does not belong to the selected PPMP.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'office_id.required' => 'Please select an office first.',
            'office_id.exists' => 'The selected office does not exist.',
            'ppmp_id.required' => 'Please select a PPMP.',
            'ppmp_id.exists' => 'The selected PPMP does not exist.',
            'ppmp_category_id.required' => 'Please select a PPMP Category.',
            'ppmp_category_id.exists' => 'The selected PPMP category does not exist.',
            'xlsx_file.required' => 'An XLSX file is required to create an emanating request.',
            'xlsx_file.mimes' => 'The file must be an XLSX file.',
            'xlsx_file.max' => 'The XLSX file must not exceed 10MB.',
        ];
    }
}
