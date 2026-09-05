<?php

namespace App\Services\Auth;

use App\Actions\Auth\Provider\DeleteProviderRegistrationUploadAction;
use App\Actions\Auth\Provider\StoreProviderRegistrationUploadAction;
use App\DTOs\Auth\ProviderRegistrationUploadDTO;
use App\DTOs\Auth\StoreProviderRegistrationUploadDTO;
use App\Exceptions\ProviderRegistrationUploadCapExceededException;

class ProviderRegistrationUploadService
{
    public function __construct(
        private readonly StoreProviderRegistrationUploadAction $storeProviderRegistrationUploadAction,
        private readonly DeleteProviderRegistrationUploadAction $deleteProviderRegistrationUploadAction,
    ) {}

    /**
     * @throws ProviderRegistrationUploadCapExceededException
     */
    public function store(StoreProviderRegistrationUploadDTO $dto): ProviderRegistrationUploadDTO
    {
        return $this->storeProviderRegistrationUploadAction->handle($dto);
    }

    public function delete(string $token, int $uploadId): bool
    {
        return $this->deleteProviderRegistrationUploadAction->handle($token, $uploadId);
    }
}
