<?php

namespace Modules\Orders\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\Enums\OrderDisputeResolutionEnum;

class ResolveOrderDisputeRequest extends FormRequest
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
                Rule::enum(OrderDisputeResolutionEnum::class),
            ],
            'user_percentage' => [
                Rule::requiredIf(
                    fn () => $this->input('resolution') === OrderDisputeResolutionEnum::PercentageSplit->value
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
