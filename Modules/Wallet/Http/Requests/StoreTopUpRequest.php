<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Payment\Enums\PaymentMethodEnum;

class StoreTopUpRequest extends ApiRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', new Enum(PaymentMethodEnum::class)],
            'transaction_image' => ['required_if:payment_method,'.PaymentMethodEnum::Offline->value],
            'user_notes' => ['nullable', 'string', 'max:191'],
        ];
    }
}
