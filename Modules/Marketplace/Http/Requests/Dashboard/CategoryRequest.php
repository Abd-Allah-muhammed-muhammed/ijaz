<?php

namespace Modules\Marketplace\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;

class CategoryRequest extends FormRequest
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
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        $rules = [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = request()->route('category');
                    if ($category && (int) $value === $category->id) {
                        $fail(__('validation.category_cannot_be_own_parent'));
                    }
                },
            ],
            'icon' => [Rule::when($this->route('category'), 'nullable', ['required', 'image', 'max:2048'])],
            'translations' => ['required', 'array'],
            'fees_type' => ['required', new Enum(CategoryFeesTypeEnum::class)],
            'fees' => [
                Rule::when(CategoryFeesTypeEnum::tryFrom($this->fees_type)->isIn([CategoryFeesTypeEnum::FIXED, CategoryFeesTypeEnum::PERCENTAGE]), 'required', 'nullable'),
                'numeric',
                'min:0',
            ],
        ];
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.title'] = [
                'required',
                'string',
                'max:191',
                Rule::unique('category_translations', 'title')
                    ->where('locale', $locale)
                    ->when($this->route('category'), function ($query) {
                        return $query->whereNot('category_id', $this->route('category')->id);
                    }),
            ];

            $rules['translations.'.$locale.'.description'] = [
                'nullable',
                'string',
                'max:191',
            ];
        }

        return $rules;
    }
}
