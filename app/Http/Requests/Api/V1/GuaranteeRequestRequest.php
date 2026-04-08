<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\CheckAuthenticatableId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use MMAE\ApiResponse\Request\ApiRequest;

class GuaranteeRequestRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'provider_type' => ['required', 'string', Rule::in(['user', 'provider'])],
            'phone' => ['required', 'numeric', Rule::when($this->filled('provider_type'), fn () => new CheckAuthenticatableId($this->get('provider_type')))],
            'description' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg,pdf', 'max:2048'],
        ];
    }
}
