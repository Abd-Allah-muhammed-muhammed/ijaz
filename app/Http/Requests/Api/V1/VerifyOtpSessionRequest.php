<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class VerifyOtpSessionRequest extends ApiRequest
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
            'verification_id' => ['required', 'uuid'],
            'code' => ['required', 'string'],
            'player_id' => ['nullable', 'string'],
        ];
    }
}
