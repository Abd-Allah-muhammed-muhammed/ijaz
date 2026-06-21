<?php

namespace Modules\Wallet\Http\Requests\Provider;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class WithdrawRequestRequest extends ApiRequest
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
            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],
            'user_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
