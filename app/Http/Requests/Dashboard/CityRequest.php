<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CityRequest extends FormRequest
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
            'region_id' => ['required', 'exists:regions,id'],
            'translations' => ['required', 'array'],
        ];
        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.title'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('region_translations', 'title')
                    ->where('locale', $locale)
                    ->when($this->route('region'), function ($query) {
                        return $query->whereNot('region_id', $this->route('region')->id);
                    }),
            ];
        }

        return $rules;
    }
}
