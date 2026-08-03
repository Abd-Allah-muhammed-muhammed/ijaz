<?php

namespace Modules\Cms\Http\Requests\Api;

use App\Http\Requests\ApiRequest;
use App\Rules\ValidPhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;

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
            'phone' => ['required', 'string', 'max:20', new ValidPhoneRule(existance: false)],
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && is_string($this->input('phone'))) {
            $this->merge([
                'phone' => preg_replace('/[\s\-]+/', '', $this->input('phone')),
            ]);
        }
    }
}
