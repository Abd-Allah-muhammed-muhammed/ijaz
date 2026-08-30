<?php

namespace Modules\Cms\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Cms\Models\Page;

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
        /** @var Page|null $current */
        $current = $this->route('page');

        $rules = [
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('pages', 'slug')->ignore($current?->id),
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
