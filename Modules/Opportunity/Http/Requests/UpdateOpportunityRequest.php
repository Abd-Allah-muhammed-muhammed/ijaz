<?php

namespace Modules\Opportunity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class UpdateOpportunityRequest extends ApiRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'region_id' => ['sometimes', 'nullable', 'exists:regions,id'],
            'city_id' => ['sometimes', 'nullable', 'exists:cities,id'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:today'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ];
    }
}
