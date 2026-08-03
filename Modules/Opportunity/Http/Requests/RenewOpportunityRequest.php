<?php

namespace Modules\Opportunity\Http\Requests;

use App\Http\Requests\ApiRequest;
use App\Support\Normalize;
use Illuminate\Contracts\Validation\ValidationRule;

class RenewOpportunityRequest extends ApiRequest
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
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $expiresAt = $this->input('expires_at');
        if (is_string($expiresAt)) {
            $this->merge([
                'expires_at' => Normalize::westernDigits($expiresAt),
            ]);
        }
    }
}
