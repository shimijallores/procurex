<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Fund;
use App\Models\PPMP;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePPMPRequest extends FormRequest
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
        $rules = [
            'office_id' => ['required', 'exists:offices,id'],
            'fund_id' => [
                'required',
                Rule::exists('funds', 'id')->where(fn($query) => $query->where('office_id', $this->office_id)),
            ],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'is_addendum' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];

        // Only validate XLSX file when uploaded
        if ($this->hasFile('xlsx_file')) {
            $rules['xlsx_file'] = ['file', 'mimes:xlsx', 'max:10240'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'office_id.required' => 'Please select an office.',
            'office_id.exists' => 'The selected office is invalid.',
            'fund_id.required' => 'Please select a fund.',
            'fund_id.exists' => 'The selected fund is invalid for the selected office.',
            'fiscal_year.required' => 'The fiscal year is required.',
            'fiscal_year.integer' => 'The fiscal year must be a valid year.',
            'remarks.max' => 'The remarks must not exceed 1000 characters.',
            'xlsx_file.mimes' => 'The file must be an XLSX file.',
            'xlsx_file.max' => 'The file size must not exceed 10MB.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $selectedFund = Fund::query()
                ->where('id', $this->fund_id)
                ->where('office_id', $this->office_id)
                ->first();

            if (! $selectedFund) {
                return;
            }

            if ((int) $selectedFund->fiscal_year !== (int) $this->fiscal_year) {
                $validator->errors()->add(
                    'fiscal_year',
                    'The fiscal year must match the selected fund fiscal year.'
                );

                return;
            }

            $projectCodeId = $selectedFund->type === 'project'
                ? $selectedFund->project_code_id
                : null;

            if ($selectedFund->type === 'project' && ! $projectCodeId) {
                $validator->errors()->add('fund_id', 'The selected project fund has no project code assigned.');

                return;
            }

            if ($this->boolean('is_addendum')) {
                $existingBasePpmp = PPMP::query()
                    ->where('office_id', $this->office_id)
                    ->where('fiscal_year', $this->fiscal_year)
                    ->when(
                        $projectCodeId === null,
                        fn($query) => $query->whereNull('project_code_id'),
                        fn($query) => $query->where('project_code_id', $projectCodeId)
                    )
                    ->where('is_addendum', false)
                    ->exists();

                if (! $existingBasePpmp) {
                    $validator->errors()->add(
                        'is_addendum',
                        'Addendum requires an existing base PPMP for the selected office, fund, and fiscal year.'
                    );
                }
            }
        });
    }
}
