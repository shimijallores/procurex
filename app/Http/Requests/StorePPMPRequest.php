<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

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
            'project_id' => ['required', 'exists:projects,id'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'project_code' => ['nullable', 'string', 'max:255'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'is_addendum' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];

        // Only validate CSV file when uploaded
        if ($this->hasFile('csv_file')) {
            $rules['csv_file'] = ['file', 'mimes:csv,txt,xlsx,xls', 'max:10240'];
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
            'project_id.required' => 'Please select a project.',
            'project_id.exists' => 'The selected project is invalid.',
            'fiscal_year.required' => 'The fiscal year is required.',
            'fiscal_year.integer' => 'The fiscal year must be a valid year.',
            'account_code.max' => 'The account code must not exceed 255 characters.',
            'project_code.max' => 'The project code must not exceed 255 characters.',
            'remarks.max' => 'The remarks must not exceed 1000 characters.',
            'csv_file.mimes' => 'The file must be a CSV file.',
            'csv_file.max' => 'The file size must not exceed 10MB.',
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
            if ($this->project_id) {
                $project = Project::with('fund')->find($this->project_id);

                if ($project && $project->fund) {
                    // Check if project's fund office matches the selected office
                    if ((int)$project->fund->office_id !== (int)$this->office_id) {
                        $validator->errors()->add(
                            'project_id',
                            'The selected project belongs to a fund from a different office.'
                        );
                    }

                    // Check if project's fund fiscal year matches the selected fiscal year
                    if ((int)$project->fund->fiscal_year !== (int)$this->fiscal_year) {
                        $validator->errors()->add(
                            'fiscal_year',
                            'The fiscal year does not match the project\'s fund fiscal year.'
                        );
                    }
                } elseif ($project && !$project->fund) {
                    $validator->errors()->add(
                        'project_id',
                        'The selected project does not have an associated fund.'
                    );
                }
            }
        });
    }
}
