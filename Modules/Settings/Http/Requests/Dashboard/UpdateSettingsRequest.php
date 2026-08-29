<?php

namespace Modules\Settings\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Enums\SettingGroupEnum;
use Modules\Settings\Support\SettingValueRules;

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

            foreach ($values as $key => $value) {
                $suffixRules = SettingValueRules::forKey((string) $key);

                if ($suffixRules === null) {
                    continue;
                }

                $fieldValidator = ValidatorFacade::make(
                    ['value' => $value],
                    ['value' => $suffixRules],
                    [],
                    ['value' => (string) $key],
                );

                if ($fieldValidator->fails()) {
                    foreach ($fieldValidator->errors()->all() as $message) {
                        $validator->errors()->add("values.{$key}", $message);
                    }
                }
            }
        });
    }
}
