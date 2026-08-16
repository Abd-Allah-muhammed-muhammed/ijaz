<?php

namespace Modules\Opportunity\Http\Requests;

use App\Http\Requests\ApiRequest;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Opportunity\Enums\OpportunityStatusEnum;

class ListActorOpportunitiesRequest extends ApiRequest
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
            'status' => [
                'sometimes',
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    foreach ($value as $status) {
                        if (! is_string($status) || OpportunityStatusEnum::tryFrom($status) === null) {
                            $fail(__('validation.enum', ['attribute' => $attribute]));

                            return;
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Normalize status the same way GuarantorFiltersData::fromRequest does:
     * a single value, a comma-separated string, or an array.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            return;
        }

        $raw = $this->input('status');
        $statuses = null;

        if (is_array($raw)) {
            $statuses = $raw;
        } elseif (is_string($raw) && str_contains($raw, ',')) {
            $statuses = array_map('trim', explode(',', $raw));
        } elseif (is_string($raw) && $raw !== '') {
            $statuses = [$raw];
        }

        if ($statuses !== null) {
            $this->merge(['status' => $statuses]);
        }
    }

    /**
     * @return array<int, string>|null
     */
    public function status(): ?array
    {
        $value = $this->validated('status');

        if (! is_array($value) || $value === []) {
            return null;
        }

        return array_values($value);
    }
}
