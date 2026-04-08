<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'translations' => ['required', 'array'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'car_brand_id' => ['required', 'exists:car_brands,id'],
        ];
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.name'] = [
                'required', 'string', 'max:255',
                Rule::unique('car_type_translations', 'name')
                    ->where('locale', $locale)
                    ->when($this->car_type, function ($query) {
                        return $query->whereNot('car_type_id', $this->car_type->id);
                    }),
            ];
        }

        return $rules;
    }
}
