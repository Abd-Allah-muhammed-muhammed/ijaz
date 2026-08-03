<?php

namespace Modules\Opportunity\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreOfferRequest extends ApiRequest
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
            'price' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
