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
        ];
    }

    public function messages(): array
    {
        return [
            'batch_no.required' => 'The batch number is required.',
            'batch_no.unique' => 'This batch number already exists.',
        ];
    }
}
