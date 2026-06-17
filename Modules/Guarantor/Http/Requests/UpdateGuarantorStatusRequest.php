<?php

namespace Modules\Guarantor\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Guarantor\Enums\GuarantorStatusEnum;

class UpdateGuarantorStatusRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(GuarantorStatusEnum::class)],
            'reason' => [
                Rule::requiredIf(fn () => in_array(
                    $this->input('status'),
                    [
                        GuarantorStatusEnum::Rejected->value,
                        GuarantorStatusEnum::Cancelled->value,
                    ]
                )),
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
