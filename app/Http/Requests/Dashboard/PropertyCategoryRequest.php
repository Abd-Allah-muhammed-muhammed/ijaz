<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyCategoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'translations.*.title' => ['required', 'string', 'max:255', Rule::unique('propertiy_category_translations', 'title')->ignore($this->propertiy_category?->id, 'propertiy_category_id')],
            'parent_id' => ['nullable', 'integer', 'exists:propertiy_categories,id'],
            'is_active' => ['boolean'],
        ];

    }
}
