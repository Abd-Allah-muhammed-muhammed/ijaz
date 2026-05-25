<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyTypeRequest extends FormRequest
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
            'translations' => ['required', 'array'],
            'translations.*.name' => ['required', 'string', 'max:255', Rule::unique('property_type_translations', 'name')->ignore($this->property_type)],
            'is_active' => ['boolean'],
        ];
    }
}
