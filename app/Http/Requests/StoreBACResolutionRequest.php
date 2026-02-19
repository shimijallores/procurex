<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBACResolutionRequest extends FormRequest
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
        return [
            'aoq_id' => ['required', 'integer', 'exists:aoqs,id', 'unique:bac_resolutions,aoq_id'],
            'resolution_date' => ['required', 'date'],
            'meeting_date' => ['nullable', 'date'],
        ];
    }
}
