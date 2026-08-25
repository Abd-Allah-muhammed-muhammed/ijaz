<?php

namespace Modules\Guarantor\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;

class ResolveGuarantorDisputeRequest extends FormRequest
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
            'resolution' => [
                'required',
                'string',
                Rule::enum(GuarantorDisputeResolutionEnum::class),
            ],
            'requester_percentage' => [
                Rule::requiredIf(
                    fn () => $this->input('resolution') === GuarantorDisputeResolutionEnum::PercentageSplit->value
                ),
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
