<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Fund;
use App\Models\PPMP;
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
            'office_id' => ['required', 'exists:offices,id'],
            'fund_id' => ['required', 'exists:funds,id'],
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
            if (! $this->fund_id || ! $this->ppmp_category_id) {
                return;
            }

            $fund = Fund::query()->find($this->fund_id);
            if (! $fund) {
                return;
            }

            if ((int) $fund->office_id !== (int) $this->office_id) {
                $validator->errors()->add('fund_id', 'The selected fund does not belong to the selected office.');

                return;
            }

            $builder = PPMP::query()
                ->where('office_id', $fund->office_id)
                ->where('fiscal_year', $fund->fiscal_year);

            if (strtolower((string) $fund->type) === 'project' && $fund->project_code_id !== null) {
                $builder->where('project_code_id', $fund->project_code_id);
            } else {
                $builder->whereNull('project_code_id');
            }

            $ppmp = $builder->latest('id')->first();

            if (! $ppmp) {
                $validator->errors()->add('fund_id', 'No PPMP is connected to the selected fund.');

                return;
            }

            $categoryBelongsToPpmp = \App\Models\PPMPCategory::query()
                ->where('id', $this->ppmp_category_id)
                ->where('ppmp_id', $ppmp->id)
                ->exists();

            if (! $categoryBelongsToPpmp) {
                $validator->errors()->add('ppmp_category_id', 'The selected PPMP category does not belong to the PPMP connected to the selected fund.');
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
            'fund_id.required' => 'Please select a fund first.',
            'fund_id.exists' => 'The selected fund does not exist.',
            'ppmp_category_id.required' => 'Please select a PPMP Category.',
            'ppmp_category_id.exists' => 'The selected PPMP category does not exist.',
            'xlsx_file.required' => 'An XLSX file is required to create an emanating request.',
            'xlsx_file.mimes' => 'The file must be an XLSX file.',
            'xlsx_file.max' => 'The XLSX file must not exceed 10MB.',
        ];
    }
}
