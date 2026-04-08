<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PageRequest extends FormRequest
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
