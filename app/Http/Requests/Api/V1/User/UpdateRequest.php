<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use MMAE\ApiResponse\Request\ApiRequest;

class UpdateRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Partial profile update: only validate fields that are present on the request
     * (PATCH-style), matching API update conventions such as UpdateOpportunityRequest.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'f_name' => ['sometimes', 'required', 'string', 'max:255'],
            'l_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'nationality_id' => ['sometimes', 'required', 'exists:nationalities,id'],
            'image' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }
}
