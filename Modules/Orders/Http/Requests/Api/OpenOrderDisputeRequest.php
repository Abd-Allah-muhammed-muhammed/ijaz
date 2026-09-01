<?php

namespace Modules\Orders\Http\Requests\Api;

use App\Http\Requests\ApiRequest;

class OpenOrderDisputeRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
