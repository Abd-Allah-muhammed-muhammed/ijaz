<?php

namespace Modules\Opportunity\Http\Requests;

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
            'opportunity_id' => ['required', 'uuid', 'exists:opportunities,id'],
        ];
    }
}
