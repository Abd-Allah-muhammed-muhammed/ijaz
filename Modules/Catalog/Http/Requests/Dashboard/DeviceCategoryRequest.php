<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'translations.*.title' => ['required', 'string', 'max:255', Rule::unique('device_category_translations', 'title')->ignore($this->device_category?->id, 'device_category_id')],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:device_categories,id',
                function ($attribute, $value, $fail) {
                    $deviceCategory = request()->route('device_category');
                    if ($deviceCategory && (int) $value === $deviceCategory->id) {
                        $fail(__('validation.device_category_cannot_be_own_parent'));
                    }
                },
            ],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }
}
