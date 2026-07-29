<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'translations.*.title' => ['required', 'string', 'max:255', Rule::unique('property_category_translations', 'title')->ignore($this->property_category?->id, 'property_category_id')],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:property_categories,id',
                function ($attribute, $value, $fail) {
                    $propertyCategory = request()->route('property_category');
                    if ($propertyCategory && (int) $value === $propertyCategory->id) {
                        $fail(__('validation.property_category_cannot_be_own_parent'));
                    }
                },
            ],
            'is_active' => ['boolean'],
        ];

    }
}
