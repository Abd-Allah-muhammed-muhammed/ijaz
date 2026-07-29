<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarCategoryRequest extends FormRequest
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
                'exists:car_categories,id',
                function ($attribute, $value, $fail) {
                    $carCategory = request()->route('car_category');
                    if ($carCategory && (int) $value === $carCategory->id) {
                        $fail(__('validation.car_category_cannot_be_own_parent'));
                    }
                },
            ],
            'icon' => ['nullable', 'image', 'max:2048'],
            'translations' => ['required', 'array'],
        ];
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.title'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('car_category_translations', 'title')
                    ->where('locale', $locale)
                    ->when($this->route('car_category'), function ($query) {
                        return $query->whereNot('car_category_id', $this->route('car_category')->id);
                    }),
            ];

        }

        return $rules;
    }
}
