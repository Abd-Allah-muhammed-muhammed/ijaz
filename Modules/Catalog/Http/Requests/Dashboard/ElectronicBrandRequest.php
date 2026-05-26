<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElectronicBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'translations' => ['required', 'array'],
            'translations.*.name' => ['required', 'string', 'max:255', Rule::unique('electronic_brand_translations', 'name')->ignore($this->electronic_brand?->id, 'electronic_brand_id')],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
