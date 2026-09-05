<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use App\Models\ProviderRegistrationUpload;
use Illuminate\Support\Facades\Storage;

class DeleteProviderRegistrationUploadAction
{
    public function __construct(
        private readonly ProviderRegistrationUploadRepositoryInterface $repository,
    ) {}

    public function handle(string $token, int $uploadId): bool
    {
        $upload = $this->repository->findByIdAndToken($uploadId, $token);

        if ($upload === null) {
            return false;
        }

        $this->deleteTempFile($upload);
        $this->repository->delete($upload);

        return true;
    }

    public function deleteTempFile(ProviderRegistrationUpload $upload): void
    {
        $disk = (string) config('provider_registration.temp_disk');

        if ($upload->path !== '' && Storage::disk($disk)->exists($upload->path)) {
            Storage::disk($disk)->delete($upload->path);
        }
    }
}
