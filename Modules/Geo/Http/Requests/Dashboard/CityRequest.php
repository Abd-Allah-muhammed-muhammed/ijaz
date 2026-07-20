<?php

namespace Modules\Geo\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        $rules = [
            'region_id' => ['required', 'exists:regions,id'],
            'translations' => ['required', 'array'],
        ];
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.title'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('city_translations', 'title')
                    ->where('locale', $locale)
                    ->when($this->route('city'), function ($query) {
                        return $query->whereNot('city_id', $this->route('city')->id);
                    }),
            ];
        }

        return $rules;
    }
}
