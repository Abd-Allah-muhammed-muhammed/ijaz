<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\ProviderProfileFileUploadResult;
use App\Models\Provider;
use Illuminate\Http\UploadedFile;

class ReplaceProviderProfileFileAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(Provider $provider, string $field, UploadedFile $file): ProviderProfileFileUploadResult
    {
        $media = $this->repository->replaceTypeFileMedia($provider, $field, $file);

        return ProviderProfileFileUploadResult::fromMedia($media);
    }
}
