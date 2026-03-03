<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
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
            'emanating_id'          => ['required', 'integer', 'exists:emanatings,id'],
            'office_id'             => ['required', 'integer', 'exists:offices,id'],
            'fund_id'               => ['required', 'integer', 'exists:funds,id'],
            'pr_no'                 => ['nullable', 'string', 'max:50'],
            'pr_date'               => [
                'required',
                Rule::date()->afterToday(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $dayOfWeek = (int) date('N', strtotime($value));
                    if ($dayOfWeek >= 6) {
                        $fail('The PR date must not be a weekend.');
                    }
                },
            ],
            'sai_no'                => ['nullable', 'string', 'max:50'],
            'sai_date'              => ['nullable', 'date'],
            'requested_by_name'     => ['nullable', 'string', 'max:255'],
            'requested_by_designation' => ['nullable', 'string', 'max:255'],
            'purpose'               => ['nullable', 'string'],
            'total_amount'          => ['nullable', 'numeric', 'min:0'],
            'status'                => ['nullable', 'string', 'in:draft,returned,approved,cancelled'],
            'remarks'               => ['nullable', 'string'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.emanating_item_id' => ['required', 'integer', 'exists:emanating_items,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'     => ['required', 'numeric', 'min:0'],
            'items.*.vat_applicable' => ['boolean'],
            'items.*.vat_rate'      => ['nullable', 'numeric', 'min:0', 'max:1'],
            'items.*.remarks'       => ['nullable', 'string'],
        ];
    }
}
