<?php

namespace App\Http\Requests\Provider\Auth;

use App\Enums\ProviderTypeFilesEnum;
use App\Models\Provider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Marketplace\Models\ProviderType;

class UploadProviderProfileFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'field' => ['required', 'string', Rule::in($this->allowedFieldsForProvider())],
            // Match UpdateProfileRequest type-file rules (PDF, max 8192 KB).
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:8192'],
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedFieldsForProvider(): array
    {
        $provider = auth('provider')->user();
        if (! $provider instanceof Provider || $provider->provider_type_id === null) {
            return [];
        }

        $type = ProviderType::query()->find($provider->provider_type_id);
        if ($type === null) {
            return [];
        }

        /** @var array<string, bool> $files */
        $files = is_array($type->files) ? $type->files : [];

        $enabled = array_keys(array_filter($files));

        $enumValues = array_map(
            static fn (ProviderTypeFilesEnum $case): string => $case->value,
            ProviderTypeFilesEnum::cases(),
        );

        return array_values(array_intersect($enabled, $enumValues));
    }
}
