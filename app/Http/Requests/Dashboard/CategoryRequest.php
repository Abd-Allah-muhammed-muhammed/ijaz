<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\CategoryFeesTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        $rules = [
            'parent_id' => 'nullable|exists:categories,id',
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
                'max:255',
                Rule::unique('category_translations', 'title')
                    ->where('locale', $locale)
                    ->when($this->route('category'), function ($query) {
                        return $query->whereNot('category_id', $this->route('category')->id);
                    }),
            ];

            $rules['translations.'.$locale.'.description'] = [
                'nullable',
                'string',
            ];
        }

        return $rules;
    }
}
