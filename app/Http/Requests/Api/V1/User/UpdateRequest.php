<?php

namespace App\Http\Requests\Api\V1\User;

use App\Models\User;
use App\Rules\ValidPhoneRule;
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
        /** @var User $user */
        $user = $this->user();

        return [
            'f_name' => ['sometimes', 'required', 'string', 'max:255'],
            'l_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            // Pass the authenticated user so uniqueness ignores the caller's own phone.
            'phone' => ['sometimes', 'required', 'string', 'max:20', new ValidPhoneRule($user)],
            'nationality_id' => ['sometimes', 'required', 'exists:nationalities,id'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'image' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Strip common phone formatting (spaces/dashes) so mobile-submitted
     * "05 1234 5678" style values validate and normalize like bare digits.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('phone') || ! is_string($this->input('phone'))) {
            return;
        }

        $this->merge([
            'phone' => preg_replace('/[\s\-]+/', '', $this->input('phone')),
        ]);
    }
}
