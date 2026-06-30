<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRFQRequest extends FormRequest
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
        $rfqId = $this->route('rfq')?->id;

        return [
            'svp_no' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/', 'unique:rfqs,svp_no,'.$rfqId],
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
            'items.*.id' => ['nullable', 'integer', 'exists:rfq_items,id'],
            'items.*.pr_item_id' => ['required', 'integer', 'exists:purchase_request_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
