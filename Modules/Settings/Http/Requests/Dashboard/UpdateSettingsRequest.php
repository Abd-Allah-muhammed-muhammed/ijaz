<?php

namespace Modules\Settings\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Enums\SettingGroupEnum;

class UpdateSettingsRequest extends FormRequest
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
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['nullable', 'string'],
            'is_public' => ['nullable', 'array'],
            'is_public.*' => ['nullable', 'boolean'],
            'group' => ['nullable', Rule::enum(SettingGroupEnum::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var array<string, mixed> $values */
            $values = $this->input('values', []);

            if ($values === []) {
                return;
            }

            $existing = app(SettingRepositoryInterface::class)
                ->all()
                ->pluck('key')
                ->all();

            foreach (array_keys($values) as $key) {
                if (! in_array($key, $existing, true)) {
                    $validator->errors()->add(
                        "values.{$key}",
                        __('validation.exists', ['attribute' => $key]),
                    );
                }
            }
        });
    }
}
