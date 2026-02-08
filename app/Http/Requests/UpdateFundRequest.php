<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFundRequest extends FormRequest
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
        $fund = $this->route('fund');
        $fundId = $fund instanceof \App\Models\Fund ? $fund->id : $fund;

        $rules = [
            'office_id' => ['required', 'exists:offices,id'],
            'code' => ['required', 'string', 'max:255', 'unique:funds,code,'.$fundId],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:general,project'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];

        // Only validate file fields when they are present and not null
        if ($this->file('work_program')) {
            $rules['work_program'] = ['file', 'mimes:pdf', 'max:10240'];
        }

        if ($this->file('project_brief')) {
            $rules['project_brief'] = ['file', 'mimes:pdf', 'max:10240'];
        }

        if ($this->file('project_proposal')) {
            $rules['project_proposal'] = ['file', 'mimes:pdf', 'max:10240'];
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
            'code.required' => 'The fund code is required.',
            'code.unique' => 'This fund code is already taken.',
            'name.required' => 'The fund name is required.',
            'type.required' => 'Please select a fund type.',
            'type.in' => 'The fund type must be either general or project.',
            'fiscal_year.required' => 'The fiscal year is required.',
            'fiscal_year.integer' => 'The fiscal year must be a valid year.',
            'work_program.mimes' => 'Work program must be a PDF file.',
            'work_program.max' => 'Work program file size must not exceed 10MB.',
            'project_brief.mimes' => 'Project brief must be a PDF file.',
            'project_brief.max' => 'Project brief file size must not exceed 10MB.',
            'project_proposal.mimes' => 'Project proposal must be a PDF file.',
            'project_proposal.max' => 'Project proposal file size must not exceed 10MB.',
        ];
    }
}
