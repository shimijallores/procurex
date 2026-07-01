<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_no' => ['required', 'string', 'max:255', 'unique:batches,batch_no'],
            'earmark_date_from' => ['nullable', 'date'],
            'earmark_date_to' => ['nullable', 'date', 'after_or_equal:earmark_date_from'],
            'is_locked' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'batch_no.required' => 'The batch number is required.',
            'batch_no.unique' => 'This batch number already exists.',
            'earmark_date_to.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
