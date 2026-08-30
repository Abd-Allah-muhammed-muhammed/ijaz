<?php

namespace Modules\Cms\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation mirrors Catalog Bank logo uploads (image mimes + 512 KB max).
 */
class UploadPageContentImageRequest extends FormRequest
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
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:512'],
        ];
    }
}
