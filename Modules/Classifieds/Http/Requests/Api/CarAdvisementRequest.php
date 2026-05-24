<?php

namespace Modules\Classifieds\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use JsonException;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;

class CarAdvisementRequest extends ApiRequest
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
            'usage_status' => ['required', new Enum(UsageStatusEnum::class)],
            'car_brand_id' => ['required', 'exists:car_brands,id'],
            'car_type_id' => ['required', 'exists:car_types,id'],
            'car_category_id' => ['nullable', 'exists:car_categories,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'transmission' => ['nullable', 'string', 'max:255'],
            'fuel_type' => ['nullable', 'string', 'max:255'],
            'engine_size' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'show_price' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:20'],
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
