<?php

namespace Modules\Opportunity\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\Opportunity\Enums\OpportunityStatusEnum;

class ListActorOpportunitiesRequest extends ApiRequest
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
            'status' => ['sometimes', 'nullable', Rule::enum(OpportunityStatusEnum::class)],
        ];
    }

    public function status(): ?string
    {
        $value = $this->validated('status');

        return $value !== null ? (string) $value : null;
    }
}
