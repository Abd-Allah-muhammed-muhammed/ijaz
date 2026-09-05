<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use App\DTOs\Auth\ProviderRegistrationUploadDTO;
use App\DTOs\Auth\StoreProviderRegistrationUploadDTO;
use App\Exceptions\ProviderRegistrationUploadCapExceededException;
use RuntimeException;

class StoreProviderRegistrationUploadAction
{
    public function __construct(
        private readonly ProviderRegistrationUploadRepositoryInterface $repository,
    ) {}

    /**
     * @throws ProviderRegistrationUploadCapExceededException
     */
    public function handle(StoreProviderRegistrationUploadDTO $dto): ProviderRegistrationUploadDTO
    {
        $maxUploads = (int) config('provider_registration.max_uploads_per_token');
        $maxBytes = (int) config('provider_registration.max_bytes_per_token');
        $disk = (string) config('provider_registration.temp_disk');
        $directory = (string) config('provider_registration.temp_directory');

        $currentCount = $this->repository->countForToken($dto->token);
        if ($currentCount >= $maxUploads) {
            throw new ProviderRegistrationUploadCapExceededException(
                __('provider_registration.uploads_cap_exceeded', ['max' => $maxUploads]),
            );
        }

        $incomingSize = $dto->file->getSize() ?: 0;
        $currentBytes = $this->repository->sumBytesForToken($dto->token);
        if (($currentBytes + $incomingSize) > $maxBytes) {
            throw new ProviderRegistrationUploadCapExceededException(
                __('provider_registration.uploads_bytes_cap_exceeded', [
                    'max_mb' => (int) floor($maxBytes / (1024 * 1024)),
                ]),
            );
        }

        $storedPath = $dto->file->store($directory, $disk);

        if ($storedPath === false) {
            throw new RuntimeException('Provider registration temp upload store failed.');
        }

        $upload = $this->repository->create([
            'token' => $dto->token,
            'field' => $dto->field,
            'path' => $storedPath,
            'original_name' => $dto->file->getClientOriginalName(),
            'mime_type' => $dto->file->getMimeType() ?: 'application/octet-stream',
            'size' => $incomingSize,
            'created_at' => now(),
        ]);

        return ProviderRegistrationUploadDTO::fromModel($upload);
    }
}
