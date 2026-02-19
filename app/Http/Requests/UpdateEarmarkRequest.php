<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEarmarkRequest extends FormRequest
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
        $earmarkId = $this->route('earmark')?->id;

        return [
            'fund_id'          => ['required', 'integer', 'exists:funds,id'],
            'earmark_no'       => ['required', 'string', 'max:100', Rule::unique('earmarks', 'earmark_no')->ignore($earmarkId)],
            'earmark_date'     => ['required', 'date'],
            'certified_amount' => ['required', 'numeric', 'min:0'],
            'expense_class'    => ['nullable', 'string', 'max:255'],
            'resolution_no'    => ['nullable', 'string', 'max:100'],
            'ordinance_no'     => ['nullable', 'string', 'max:100'],
            'ordinance_date'   => ['nullable', 'date'],
            'remarks'          => ['nullable', 'string'],
        ];
    }
}
