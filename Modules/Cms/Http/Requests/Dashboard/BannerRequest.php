<?php

namespace Modules\Cms\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
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
        return [
            'link' => ['nullable', 'url'],
            'image' => [Rule::when($this->banner, 'nullable', 'required'), 'image', 'max:6000'],
        ];
    }
}
