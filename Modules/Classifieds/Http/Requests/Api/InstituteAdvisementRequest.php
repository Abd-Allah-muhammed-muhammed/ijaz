<?php

namespace Modules\Classifieds\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use JsonException;
use MMAE\ApiResponse\Request\ApiRequest;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;

class InstituteAdvisementRequest extends ApiRequest
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
            'type' => ['required', new Enum(InstituteTypeEnum::class)],
            'study_type' => ['required', new Enum(StudyTypeEnum::class)],
            'study_level' => ['nullable', new Enum(StudyLevelEnum::class)],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'discounted_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'days_count' => ['nullable', 'integer', 'min:1'],
            'hours_count' => ['nullable', 'integer', 'min:1'],
            'goals' => ['nullable', 'string'],
            'payment_notes' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:500'],
            'registration_url' => ['nullable', 'url', 'max:500'],
            'course_url' => ['nullable', 'url', 'max:500'],
            'quality_url' => ['nullable', 'url', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'study_start' => ['nullable', 'date'],
            'study_end' => ['nullable', 'date', 'after_or_equal:study_start'],
            'options' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
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
    }
}
