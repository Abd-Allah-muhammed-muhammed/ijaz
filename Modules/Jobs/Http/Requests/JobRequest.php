<?php

namespace Modules\Jobs\Http\Requests;

use App\Enums\Jobs\JobTypeEnum;
use App\Rules\ValidPhoneRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use JsonException;
use MMAE\ApiResponse\Request\ApiRequest;

class JobRequest extends ApiRequest
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
            'expected_salary' => ['required', 'numeric', 'gt:0'],
            'expired_at' => ['required', 'date', 'after:today'],
            'contact_number' => ['required', 'string', 'max:20', new ValidPhoneRule(existance: false)],
            'city_id' => ['required', 'exists:cities,id'],
            'region_id' => ['required', 'exists:regions,id'],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:'.(1024 * 5)],
            'type' => ['required', new Enum(JobTypeEnum::class)],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['sometimes', 'exists:skills,id'],
        ];
    }

    /**
     * @throws JsonException
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->get('skills'))) {
            $this->merge([
                'skills' => json_decode($this->get('skills'), true, 512, JSON_THROW_ON_ERROR),
            ]);
        }
    }
}
