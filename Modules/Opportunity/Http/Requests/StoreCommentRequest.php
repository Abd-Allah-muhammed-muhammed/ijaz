<?php

namespace Modules\Opportunity\Http\Requests;

use App\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreCommentRequest extends ApiRequest
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
            'body' => ['required', 'string', 'max:1000'],
        ];
    }
}
