<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $projectCode = $this->route('project_code');

        return [
            'office_id' => ['required', 'exists:offices,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('project_codes', 'code')->ignore($projectCode)],
            'name' => ['required', 'string', 'max:255', Rule::unique('project_codes', 'name')->ignore($projectCode)],
        ];
    }
}
