<?php

namespace App\Actions\Provider;

use App\Actions\Provider\SyncProviderCategoriesAndSkillsAction as SyncProviderCategoriesAndSkills;
use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\UpdateProviderDTO;
use App\Enums\ProviderTypeFilesEnum;
use App\Models\Provider;
use App\Support\HandlesTransactionalFileUpload;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Throwable;

class UpdateProviderAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
        private readonly SyncProviderCategoriesAndSkills $syncProviderCategoriesAndSkillsAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Provider $provider, UpdateProviderDTO $dto): Provider
    {
        return $this->storeFileWithCleanup(
            file: $dto->logo,
            directory: 'providers',
            disk: 'public',
            previousPath: $dto->logo !== null ? $provider->logo : null,
            dbWork: function (?string $logoPath) use ($provider, $dto): Provider {
                $data = [
                    'name' => $dto->name,
                    'provider_type_id' => $dto->provider_type_id,
                    'region_id' => $dto->region_id,
                    'city_id' => $dto->city_id,
                    'address' => $dto->address,
                    'phone' => Phone::make($dto->phone)->toString(),
                    'email' => $dto->email,
                    'iban' => $dto->iban,
                    'about' => $dto->about,
                ];

                if ($logoPath !== null) {
                    $data['logo'] = $logoPath;
                }

                if (filled($dto->password)) {
                    $data['password'] = $dto->password;
                }

                $this->replaceMedia($provider, $dto->mediaFiles);
                $this->repository->update($provider, $data);
                $this->syncProviderCategoriesAndSkillsAction->handle($provider, $dto->categories);

                return $provider;
            },
        );
    }

    /**
     * @param  array<string, UploadedFile>  $mediaFiles
     */
    private function replaceMedia(Provider $provider, array $mediaFiles): void
    {
        foreach (ProviderTypeFilesEnum::cases() as $file) {
            if (! isset($mediaFiles[$file->value])) {
                continue;
            }

            $provider
                ->clearMediaCollection($file->value)
                ->addMedia($mediaFiles[$file->value])
                ->toMediaCollection($file->value, 'local');
        }
    }
}
