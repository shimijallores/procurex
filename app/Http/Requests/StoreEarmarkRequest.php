<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEarmarkRequest extends FormRequest
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
            'purchase_request_id' => ['required', 'integer', 'exists:purchase_requests,id'],
            'fund_id'             => ['required', 'integer', 'exists:funds,id'],
            'earmark_no'          => ['required', 'string', 'max:100', 'unique:earmarks,earmark_no'],
            'earmark_date'        => ['required', 'date'],
            'certified_amount'    => ['required', 'numeric', 'min:0'],
            'expense_class'       => ['nullable', 'string', 'max:255'],
            'resolution_no'       => ['nullable', 'string', 'max:100'],
            'ordinance_no'        => ['nullable', 'string', 'max:100'],
            'ordinance_date'      => ['nullable', 'date'],
            'remarks'             => ['nullable', 'string'],
        ];
    }
}
