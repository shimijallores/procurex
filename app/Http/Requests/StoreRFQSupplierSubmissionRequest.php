<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRFQSupplierSubmissionRequest extends FormRequest
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
            'submitted_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'unit_prices' => ['nullable', 'array'],
            'unit_prices.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
