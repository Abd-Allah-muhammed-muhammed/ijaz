<?php

namespace App\Http\Requests;

use App\Enums\ProviderTypeFilesEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderTypeRequest extends FormRequest
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
        $fils = ProviderTypeFilesEnum::cases();
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales'));
        $type = $this->route('provider_type');
        $rules = [
            'files' => ['required', 'array', 'max:'.count($fils)],
            'image' => [
                Rule::when($type, 'nullable', 'required'),
                'mimetypes:image/jpeg,image/png,image/webp,image/jpg,image/gif,image/svg+xml',
                'max:2048', // 2MB
            ],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'exists:categories,id'],
        ];
        foreach ($fils as $file) {
            $rules['files.'.$file->value] = [
                'required',
                'boolean',
            ];
        }

        foreach ($supportedLocales as $locale) {
            $rules['translations.'.$locale.'.name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('provider_type_translations', 'name')
                    ->where('locale', $locale)
                    ->when($type, function ($query) use ($type) {
                        return $query->whereNot('provider_type_id', $type->id);
                    }),
            ];

            $rules['translations.'.$locale.'.description'] = [
                'nullable',
                'string',
            ];
        }

        return $rules;
    }
}
