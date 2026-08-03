<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Auth\OtpPurposeEnum;
use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class VerifyOTPRequest extends ApiRequest
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
            'type' => ['required', Rule::in(OtpPurposeEnum::userApiValues())],
            'otp' => 'required|string',
        ];
    }
}
