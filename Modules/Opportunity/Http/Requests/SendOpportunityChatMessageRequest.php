<?php

namespace Modules\Opportunity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class SendOpportunityChatMessageRequest extends ApiRequest
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
            'content' => ['required_without:files', 'nullable', 'string'],
            'files' => ['required_without:content', 'nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ];
    }
}
