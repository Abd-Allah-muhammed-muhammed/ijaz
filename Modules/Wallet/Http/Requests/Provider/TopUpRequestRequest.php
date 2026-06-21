<?php

namespace Modules\Wallet\Http\Requests\Provider;

use App\Enums\Payment\PaymentDriverEnum;
use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use MMAE\ApiResponse\Request\ApiRequest;

class TopUpRequestRequest extends ApiRequest
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
            'payment_method' => [
                'required',
                'string',
                (new Enum(PaymentMethodEnum::class)),
            ],
            'payment_driver' => [
                'required_if:payment_method,'.PaymentMethodEnum::Online->value,
                'string',
                (new Enum(PaymentDriverEnum::class))
                    ->when(app()->isProduction(), fn ($rule) => $rule->except([PaymentDriverEnum::Testing])),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],
            'user_notes' => [
                'required_if:payment_method,'.PaymentMethodEnum::Offline->value,
                'string',
                'max:1000',
            ],
            'transaction_image' => [
                'required_if:payment_method,'.PaymentMethodEnum::Offline->value,
                'image',
                'max:'.(2 * 1024),
            ],
        ];
    }
}
