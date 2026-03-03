<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PPMP;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePPMPRequest extends FormRequest
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
                'required',
                Rule::exists('project_codes', 'id')->where(fn($query) => $query->where('office_id', $this->office_id)),
            ],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'is_addendum' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
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
            'office_id.required' => 'Please select an office.',
            'office_id.exists' => 'The selected office is invalid.',
            'project_code_id.required' => 'Please select a project code.',
            'project_code_id.exists' => 'The selected project code is invalid for the selected office.',
            'fiscal_year.required' => 'The fiscal year is required.',
            'fiscal_year.integer' => 'The fiscal year must be a valid year.',
            'remarks.max' => 'The remarks must not exceed 1000 characters.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('is_addendum')) {
                /** @var PPMP|null $currentPpmp */
                $currentPpmp = $this->route('ppmp');

                $existingBasePpmp = PPMP::query()
                    ->where('office_id', $this->office_id)
                    ->where('project_code_id', $this->project_code_id)
                    ->where('fiscal_year', $this->fiscal_year)
                    ->where('is_addendum', false)
                    ->when($currentPpmp, fn($query) => $query->where('id', '!=', $currentPpmp->id))
                    ->exists();

                if (! $existingBasePpmp) {
                    $validator->errors()->add(
                        'is_addendum',
                        'Addendum requires an existing base PPMP for the selected office, project code, and fiscal year.'
                    );
                }
            }
        });
    }
}
