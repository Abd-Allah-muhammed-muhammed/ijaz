<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        $rules = [
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'translations' => ['required', 'array'],
        ];
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.name'] = ['required', 'string', 'max:255', Rule::unique('car_brand_translations', 'name')->where('locale', $locale)->when($this->car_brand, function ($query) {
                return $query->whereNot('car_brand_id', $this->car_brand->id);
            })];
        }

        return $rules;
    }
}
