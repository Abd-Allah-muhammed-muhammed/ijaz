<?php

namespace App\Http\Requests\Api\User;

use App\Enums\Order\OfferStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use MMAE\ApiResponse\Request\ApiRequest;

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
            'status' => ['required', new Enum(OfferStatusEnum::class)],
        ];
    }
}
