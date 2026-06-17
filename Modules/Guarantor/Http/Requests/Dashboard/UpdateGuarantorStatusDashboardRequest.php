<?php

namespace Modules\Guarantor\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Guarantor\Enums\GuarantorStatusEnum;

class UpdateGuarantorStatusDashboardRequest extends FormRequest
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
            'status' => ['required', Rule::enum(GuarantorStatusEnum::class)],
            'reason' => [
                Rule::requiredIf(fn () => in_array(
                    $this->input('status'),
                    [
                        GuarantorStatusEnum::RejectedByAdmin->value,
                        GuarantorStatusEnum::Cancelled->value,
                        GuarantorStatusEnum::Refunded->value,
                    ],
                    true
                )),
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
