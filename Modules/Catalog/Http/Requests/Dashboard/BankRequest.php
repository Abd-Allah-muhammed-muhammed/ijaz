<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankRequest extends FormRequest
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
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:512'],
            'is_active' => ['nullable', 'boolean'],
        ];

        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('bank_translations', 'name')
                    ->where('locale', $locale)
                    ->ignore($this->bank?->id, 'bank_id'),
            ];
        }

        return $rules;
    }
}
