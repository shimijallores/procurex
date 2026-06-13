<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Fund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'office_id' => ['required', 'exists:offices,id'],
            'project_code_id' => [
                'nullable',
                Rule::exists('project_codes', 'id')->where(fn ($query) => $query->where('office_id', $this->office_id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:general,project'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'work_program' => ['nullable', 'file', 'mimes:docx', 'max:51200'],
            'project_brief' => ['nullable', 'file', 'mimes:docx', 'max:51200'],
            'project_proposal' => ['nullable', 'file', 'mimes:docx', 'max:51200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') === 'project' && ! $this->filled('project_code_id')) {
                $validator->errors()->add('project_code_id', 'Please select a project code for project type funds.');
            }

            $fund = $this->route('fund');

            if ($this->input('type') !== 'project') {
                return;
            }

            if (! $fund instanceof Fund) {
                return;
            }

            $fund->loadMissing(['project.workProgram', 'project.projectBrief', 'project.projectProposal']);

            $hasWorkProgram = (bool) $fund->project?->workProgram;
            $hasProjectBrief = (bool) $fund->project?->projectBrief;
            $hasProjectProposal = (bool) $fund->project?->projectProposal;

            if (! $this->hasFile('work_program') && ! $hasWorkProgram) {
                $validator->errors()->add('work_program', 'Work program is required for project type funds.');
            }

            if (! $this->hasFile('project_brief') && ! $hasProjectBrief) {
                $validator->errors()->add('project_brief', 'Project brief is required for project type funds.');
            }

            if (! $this->hasFile('project_proposal') && ! $hasProjectProposal) {
                $validator->errors()->add('project_proposal', 'Project proposal is required for project type funds.');
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
            'project_code_id.exists' => 'The selected project code is invalid for the selected office.',
            'name.required' => 'The fund name is required.',
            'type.required' => 'Please select a fund type.',
            'type.in' => 'The fund type must be either general or project.',
            'fiscal_year.required' => 'The fiscal year is required.',
            'fiscal_year.integer' => 'The fiscal year must be a valid year.',
            'work_program.mimes' => 'Work program must be a DOCX file.',
            'work_program.max' => 'Work program file size must not exceed 50MB.',
            'project_brief.mimes' => 'Project brief must be a DOCX file.',
            'project_brief.max' => 'Project brief file size must not exceed 50MB.',
            'project_proposal.mimes' => 'Project proposal must be a DOCX file.',
            'project_proposal.max' => 'Project proposal file size must not exceed 50MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== 'project') {
            $this->merge([
                'project_code_id' => null,
            ]);
        }
    }
}
