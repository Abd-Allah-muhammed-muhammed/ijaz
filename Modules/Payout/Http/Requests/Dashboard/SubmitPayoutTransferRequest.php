<?php

namespace Modules\Payout\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPayoutTransferRequest extends FormRequest
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
            'gateway_reference' => 'required|string|max:255',
            'proof_image' => ['required', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ];
    }
}
