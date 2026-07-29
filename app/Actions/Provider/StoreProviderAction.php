<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\StoreProviderDTO;
use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\ProviderTypeFilesEnum;
use App\Models\Provider;
use App\Support\HandlesTransactionalFileUpload;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Throwable;

class StoreProviderAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreProviderDTO $dto): Provider
    {
        return $this->storeFileWithCleanup(
            file: $dto->logo,
            directory: 'providers',
            disk: 'public',
            dbWork: function (?string $logoPath) use ($dto): Provider {
                $provider = $this->repository->create([
                    'name' => $dto->name,
                    'provider_type_id' => $dto->provider_type_id,
                    'region_id' => $dto->region_id,
                    'city_id' => $dto->city_id,
                    'address' => $dto->address,
                    'phone' => Phone::make($dto->phone)->toString(),
                    'email' => $dto->email,
                    'iban' => $dto->iban,
                    'about' => $dto->about,
                    'password' => $dto->password,
                    'logo' => $logoPath,
                    'status' => ProviderStatusEnum::Pending,
                ]);

                $provider->code = date('dmy').$provider->id;
                $provider->save();

                $this->attachMedia($provider, $dto->mediaFiles);

                $categories = collect($dto->categories);
                $this->repository->syncCategories($provider, $categories->pluck('id')->toArray());
                $this->repository->syncSkills(
                    $provider,
                    $categories
                        ->map(function ($item) {
                            return array_map(
                                static fn ($skill) => ['category_id' => $item['id'], 'skill_id' => $skill],
                                $item['skills'] ?? [],
                            );
                        })
                        ->flatten(1)
                        ->toArray()
                );

                return $provider;
            },
        );
    }

    /**
     * @param  array<string, UploadedFile>  $mediaFiles
     */
    private function attachMedia(Provider $provider, array $mediaFiles): void
    {
        foreach (ProviderTypeFilesEnum::cases() as $file) {
            if (! isset($mediaFiles[$file->value])) {
                continue;
            }

            $provider
                ->addMedia($mediaFiles[$file->value])
                ->toMediaCollection($file->value, 'local');
        }
    }
}
