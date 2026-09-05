<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use App\Enums\ProviderTypeFilesEnum;
use App\Models\ProviderRegistrationUpload;
use App\Support\Auth\ProviderRegistrationFileRules;
use Illuminate\Validation\ValidationException;

class ResolveProviderRegistrationUploadsAction
{
    public function __construct(
        private readonly ProviderRegistrationUploadRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, int|string>  $uploads  field => upload id
     * @return array<string, ProviderRegistrationUpload>
     *
     * @throws ValidationException
     */
    public function handle(string $token, array $uploads): array
    {
        $resolved = [];
        $errors = [];

        foreach ($uploads as $field => $uploadId) {
            if (! is_string($field) || ! ProviderRegistrationFileRules::isAllowedField($field)) {
                continue;
            }

            if (! is_numeric($uploadId)) {
                $errors[$field] = [__('provider_registration.upload_reference_invalid', ['field' => __($field)])];

                continue;
            }

            $upload = $this->repository->findByIdTokenAndField((int) $uploadId, $token, $field);

            if ($upload === null) {
                $errors[$field] = [__('provider_registration.upload_reference_missing', ['field' => __($field)])];

                continue;
            }

            $retentionHours = (int) config('provider_registration.retention_hours');
            $expiresAt = $upload->created_at?->copy()->addHours($retentionHours);

            if ($expiresAt === null || $expiresAt->isPast()) {
                $errors[$field] = [__('provider_registration.upload_reference_expired', ['field' => __($field)])];

                continue;
            }

            $resolved[$field] = $upload;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $resolved;
    }

    /**
     * @param  array<string, ProviderRegistrationUpload>  $resolved
     * @return array<string, ProviderRegistrationUpload>
     */
    public function certificateUploads(array $resolved): array
    {
        $certificates = [];

        foreach (ProviderTypeFilesEnum::cases() as $case) {
            if (isset($resolved[$case->value])) {
                $certificates[$case->value] = $resolved[$case->value];
            }
        }

        return $certificates;
    }
}
