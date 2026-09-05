<?php

namespace App\Http\Requests\Frontend;

use App\Support\Auth\ProviderRegistrationFileRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRegistrationUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'token' => $this->route('token'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $field = (string) $this->input('field', '');

        $fileRules = ProviderRegistrationFileRules::isAllowedField($field)
            ? ProviderRegistrationFileRules::forUploadFile($field)
            : ['required', 'file'];

        return [
            'token' => ['required', 'uuid'],
            'field' => ProviderRegistrationFileRules::fieldAttributeRules(),
            // Must match ProviderRegisterRequest's historical size/mime expectations —
            // see ProviderRegistrationFileRules (single source of truth).
            'file' => $fileRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $field = (string) $this->input('field', 'file');

        return [
            'file' => __($field) !== $field ? __($field) : $field,
            'field' => __('provider_registration.field'),
            'token' => __('provider_registration.upload_token'),
        ];
    }
}
