<?php

namespace Modules\Cms\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class QuestionRequest extends FormRequest
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
        $rules = [];
        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $supportedLanguagesKey) {
            $rules["translations.{$supportedLanguagesKey}.title"] = [
                'required', 'string', 'max:255',
            ];
            $rules["translations.{$supportedLanguagesKey}.answer"] = [
                'required', 'string',
            ];
        }

        return $rules;
    }
}
