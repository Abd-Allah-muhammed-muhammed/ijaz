<?php

namespace Modules\Opportunity\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ListPublicOpportunitiesRequest extends ApiRequest
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
            'region_id' => ['sometimes', 'nullable', 'integer', 'exists:regions,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
        ];
    }

    public function regionId(): ?int
    {
        $value = $this->validated('region_id');

        return $value !== null ? (int) $value : null;
    }

    public function cityId(): ?int
    {
        $value = $this->validated('city_id');

        return $value !== null ? (int) $value : null;
    }
}
