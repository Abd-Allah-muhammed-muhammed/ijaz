<?php

namespace Modules\Classifieds\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use JsonException;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Classifieds\Enums\ElectronicConditionEnum;

class ElectronicAdvisementRequest extends ApiRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'condition' => ['required', new Enum(ElectronicConditionEnum::class)],
            'device_category_id' => ['required', 'exists:device_categories,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'show_price' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'options' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
