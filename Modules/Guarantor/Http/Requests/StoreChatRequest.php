<?php

namespace Modules\Guarantor\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreChatRequest extends ApiRequest
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
            'guarantor_request_id' => [
                'required',
                'uuid',
                'exists:guarantor_requests,id',
            ],
        ];
    }
}
