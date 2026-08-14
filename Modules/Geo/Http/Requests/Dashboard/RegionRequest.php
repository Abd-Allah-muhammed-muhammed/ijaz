<?php

namespace Modules\Geo\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegionRequest extends FormRequest
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
            'translations' => ['required', 'array'],
        ];
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.title'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('region_translations', 'title')
                    ->where('locale', $locale)
                    ->ignore($this->region?->id, 'region_id'),
            ];
        }

        return $rules;
    }
}
