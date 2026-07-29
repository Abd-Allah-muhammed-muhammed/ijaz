<?php

namespace Modules\Cms\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PageRequest extends FormRequest
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
        $rules = [
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('pages', 'slug')->ignore($this->page?->id),
            ],
        ];
        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $supportedLanguagesKey) {
            $rules["translations.{$supportedLanguagesKey}.title"] = [
                'required', 'string', 'max:255',
            ];
            $rules["translations.{$supportedLanguagesKey}.content"] = [
                'required', 'string',
            ];
        }

        return $rules;
    }
}
