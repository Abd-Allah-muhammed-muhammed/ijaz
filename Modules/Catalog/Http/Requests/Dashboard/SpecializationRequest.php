<?php

namespace Modules\Catalog\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpecializationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'translations.*.title' => ['required', 'string', 'max:255', Rule::unique('specialization_translations', 'title')->ignore($this->specialization?->id, 'specialization_id')],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:specializations,id',
                function ($attribute, $value, $fail) {
                    $specialization = request()->route('specialization');
                    if ($specialization && (int) $value === $specialization->id) {
                        $fail(__('validation.specialization_cannot_be_own_parent'));
                    }
                },
            ],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }
}
