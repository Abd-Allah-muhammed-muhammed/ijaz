<?php

namespace Modules\Guarantor\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Guarantor\Rules\CheckAuthenticatableId;

class StoreIndividualGuarantorRequest extends ApiRequest
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
            'counterparty_phone' => [
                'required',
                'string',
                new CheckAuthenticatableId('user'),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'signature' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }
}
