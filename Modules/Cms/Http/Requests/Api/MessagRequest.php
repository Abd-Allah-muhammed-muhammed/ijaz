<?php

namespace Modules\Cms\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class MessagRequest extends ApiRequest
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
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }
}
