<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use MMAE\ApiResponse\Request\ApiRequest;

class OrderRequest extends ApiRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'budget_start' => ['required', 'numeric', 'min:0'],
            'budget_end' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'expected_time' => ['nullable', 'string', 'max:191'],
            //      'city_id' => ['required', 'exists:cities,id'],
            //      'region_id' => ['required', 'exists:regions,id'],
            'skills' => ['sometimes', 'array', 'min:1'],
            'skills.*' => ['sometimes', 'exists:skills,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('skills'))) {
            $this->merge([
                'skills' => json_decode($this->input('skills'), true) ?: [],
            ]);
        }
    }
}
