<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Auth\ProviderRegistrationUploadRepositoryInterface;
use App\Models\ProviderRegistrationUpload;

class PruneExpiredProviderRegistrationUploadsAction
{
    public function __construct(
        private readonly ProviderRegistrationUploadRepositoryInterface $repository,
        private readonly DeleteProviderRegistrationUploadAction $deleteProviderRegistrationUploadAction,
    ) {}

    public function handle(): int
    {
        $retentionHours = (int) config('provider_registration.retention_hours');
        $chunkSize = (int) config('provider_registration.prune_chunk_size');
        $cutoff = now()->subHours($retentionHours);
        $deleted = 0;

        $this->repository->chunkOlderThan($cutoff, $chunkSize, function ($uploads) use (&$deleted): void {
            /** @var ProviderRegistrationUpload $upload */
            foreach ($uploads as $upload) {
                $this->deleteProviderRegistrationUploadAction->deleteTempFile($upload);
                $this->repository->delete($upload);
                $deleted++;
            }
        });

        return $deleted;
    }
}
