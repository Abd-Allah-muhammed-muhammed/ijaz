<?php

namespace Modules\Chat\Http\Requests;

use App\Http\Requests\ApiRequest;

class SendSupportMessageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required_without:files|nullable|string',
            'files' => 'required_without:content|array',
            'files.*' => 'required_without:content|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'files' => __('attachment'),
            // Wildcard covers files.0, files.1, … so Laravel never exposes the raw path.
            'files.*' => __('attachment'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Wildcard max rule — same clean copy for any oversized file index.
            'files.*.max' => __('One of your files exceeds the 5MB limit.'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
