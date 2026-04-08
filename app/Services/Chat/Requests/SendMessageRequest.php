<?php

namespace App\Services\Chat\Requests;

use MMAE\ApiResponse\Request\ApiRequest;

class SendMessageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required_without:files',
            'files' => 'required_without:content|array',
            'files.*' => 'required_without:content|file|mimes:jpeg,jpg,png,gif,pdf|max:5120',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
