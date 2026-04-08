<?php

namespace App\Http\Requests\Dashboard;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        $rules = [
            'parent_id' => 'nullable|exists:car_categories,id',
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
