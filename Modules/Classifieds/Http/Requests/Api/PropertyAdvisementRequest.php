<?php

namespace Modules\Classifieds\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use JsonException;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Classifieds\Enums\OperationEnum;

class PropertyAdvisementRequest extends ApiRequest
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
            'description' => ['required', 'string'],
            'operation' => ['required', new Enum(OperationEnum::class)],
            'property_type_id' => ['required', 'exists:property_types,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'category_id' => ['nullable', 'exists:propertiy_categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'show_price' => ['sometimes', 'boolean'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'bedrooms_count' => ['nullable', 'integer', 'min:0'],
            'bathrooms_count' => ['nullable', 'integer', 'min:0'],
            'halls_count' => ['nullable', 'integer', 'min:0'],
            'age' => ['nullable', 'integer', 'min:0'],
            'facade' => ['nullable', 'string', 'max:255'],
            'street_width' => ['nullable', 'numeric', 'min:0'],
            'street_type' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'license' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'options' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.(1024 * 5)],
        ];
    }

    /**
     * @throws JsonException
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->get('options'))) {
            $this->merge([
                'options' => json_decode($this->get('options'), true, 512, JSON_THROW_ON_ERROR),
            ]);
        }

        if ($this->has('show_price') && is_string($this->get('show_price'))) {
            $this->merge([
                'show_price' => filter_var($this->get('show_price'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
