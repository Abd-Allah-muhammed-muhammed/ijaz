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
        $minWithdraw = (float) app('settings')->get('min_withdraw_amount', 200);

        return [
            'amount' => [
                'required',
                'numeric',
                'min:'.$minWithdraw,
            ],
            'user_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $minWithdraw = (float) app('settings')->get('min_withdraw_amount', 200);

        return [
            'amount.min' => __('minimum_withdrawal_amount', ['amount' => $minWithdraw]),
        ];
    }
}
