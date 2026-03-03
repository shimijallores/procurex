<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRFQRequest extends FormRequest
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
            'pr_id' => ['nullable', 'integer', 'exists:purchase_requests,id', 'required_without:pr_no'],
            'pr_no' => ['nullable', 'string', 'max:50', 'required_without:pr_id'],
            'rfq_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $dayOfWeek = (int) date('N', strtotime($value));
                    if ($dayOfWeek >= 6) {
                        $fail('The RFQ date must not be a weekend.');
                    }
                },
            ],
            'submission_deadline' => ['nullable', 'date', 'after_or_equal:rfq_date'],
            'project_name' => ['required', 'string', 'max:255'],
            'abc_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pr_item_id' => ['required', 'integer', 'exists:purchase_request_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
