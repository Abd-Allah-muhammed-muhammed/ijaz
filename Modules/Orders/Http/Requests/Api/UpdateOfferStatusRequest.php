<?php

namespace Modules\Orders\Http\Requests\Api;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\Orders\Enums\OfferStatusEnum;

class UpdateOfferStatusRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    OfferStatusEnum::Accepted->value,
                    OfferStatusEnum::Rejected->value,
                    OfferStatusEnum::Cancelled->value,
                ]),
            ],
        ];
    }
}
