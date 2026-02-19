<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFundRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('funds', 'code')
                    ->where('office_id', $this->office_id)
                    ->where('fiscal_year', $this->fiscal_year),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:general,project'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'project_name' => ['nullable', 'string', 'max:255'],
        ];

        // Only validate file format when files are actually uploaded
        if ($this->hasFile('work_program')) {
            $rules['work_program'] = ['file', 'mimes:pdf', 'max:10240'];
        }

        if ($this->hasFile('project_brief')) {
            $rules['project_brief'] = ['file', 'mimes:pdf', 'max:10240'];
        }

        if ($this->hasFile('project_proposal')) {
            $rules['project_proposal'] = ['file', 'mimes:pdf', 'max:10240'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Require files for project type funds
            if ($this->input('type') === 'project') {
                if (! $this->hasFile('work_program')) {
                    $validator->errors()->add('work_program', 'Work program is required for project type funds.');
                }

                if (! $this->hasFile('project_brief')) {
                    $validator->errors()->add('project_brief', 'Project brief is required for project type funds.');
                }

                if (! $this->hasFile('project_proposal')) {
                    $validator->errors()->add('project_proposal', 'Project proposal is required for project type funds.');
                }
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
            'office_id.required' => 'Please select an office.',
            'office_id.exists' => 'The selected office is invalid.',
            'code.required' => 'The fund code is required.',
            'code.unique' => 'This fund code is already used for this office and fiscal year.',
            'name.required' => 'The fund name is required.',
            'type.required' => 'Please select a fund type.',
            'type.in' => 'The fund type must be either general or project.',
            'fiscal_year.required' => 'The fiscal year is required.',
            'fiscal_year.integer' => 'The fiscal year must be a valid year.',
            'work_program.required_if' => 'Work program is required for project type funds.',
            'work_program.mimes' => 'Work program must be a PDF file.',
            'work_program.max' => 'Work program file size must not exceed 10MB.',
            'project_brief.required_if' => 'Project brief is required for project type funds.',
            'project_brief.mimes' => 'Project brief must be a PDF file.',
            'project_brief.max' => 'Project brief file size must not exceed 10MB.',
            'project_proposal.required_if' => 'Project proposal is required for project type funds.',
            'project_proposal.mimes' => 'Project proposal must be a PDF file.',
            'project_proposal.max' => 'Project proposal file size must not exceed 10MB.',
        ];
    }
}
