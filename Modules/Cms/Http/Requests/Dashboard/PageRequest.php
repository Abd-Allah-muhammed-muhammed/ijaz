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
        $isComposed = is_array($this->input('composed_of_slugs'))
            && count(array_filter((array) $this->input('composed_of_slugs'))) > 0;

        /** @var Page|null $current */
        $current = $this->route('page');

        $rules = [
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('pages', 'slug')->ignore($current?->id),
            ],
            'composed_of_slugs' => ['nullable', 'array'],
            'composed_of_slugs.*' => [
                'string',
                'distinct',
                Rule::exists('pages', 'slug'),
                Rule::notIn([(string) $this->input('slug')]),
            ],
        ];

        foreach (LaravelLocalization::getSupportedLanguagesKeys() as $supportedLanguagesKey) {
            $rules["translations.{$supportedLanguagesKey}.title"] = [
                'required', 'string', 'max:255',
            ];
            $rules["translations.{$supportedLanguagesKey}.content"] = $isComposed
                ? ['nullable', 'string']
                : ['required', 'string'];
        }

        return $rules;
    }
}
