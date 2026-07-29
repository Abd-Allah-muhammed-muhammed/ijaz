<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\UpdateProviderDTO;
use App\Enums\ProviderTypeFilesEnum;
use App\Models\Provider;
use App\Support\HandlesTransactionalFileUpload;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Modules\Marketplace\Models\CategorySkill;
use Modules\Marketplace\Models\ProviderCategory;
use Throwable;

class UpdateProviderAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
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
                $this->syncCategoriesAndSkills($provider, $dto->categories);

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

    /**
     * @param  list<array{id: int|string, skills?: list<int|string>|null}>  $categoriesInput
     */
    private function syncCategoriesAndSkills(Provider $provider, array $categoriesInput): void
    {
        $categories = collect($categoriesInput)->keyBy('id');
        $old_skills = [];
        $provider->categorySkills->each(function (CategorySkill $item) use (&$old_skills) {
            $old_skills[$item->category_id][] = $item->skill_id;
        });
        $provider->providerCategories->each(function (ProviderCategory $providerCategory) use (&$categories, $old_skills, $provider) {
            $new = $categories->get($providerCategory->category_id);
            if (! $new) {
                $providerCategory->delete();
                $provider->categorySkills()
                    ->where('category_id', $providerCategory->category_id)
                    ->delete();

                return;
            }
            $skills = array_unique($new['skills'] ?? []);
            if (empty($skills)) {
                $provider->categorySkills()
                    ->where('category_id', $providerCategory->category_id)
                    ->delete();
            }
            $old_s = $old_skills[$providerCategory->category_id] ?? [];
            $to_delete = array_diff($old_s, $skills);
            if (! empty($to_delete)) {
                $provider->categorySkills
                    ->where('category_id', $providerCategory->category_id)
                    ->whereIn('skill_id', $to_delete)
                    ->delete();
            }
            $to_add = array_diff($skills, $old_s);
            if (! empty($to_add)) {
                $provider->categorySkills()->createMany(
                    array_map(static fn ($skill_id) => [
                        'category_id' => $providerCategory->category_id,
                        'skill_id' => $skill_id,
                    ], $to_add
                    ));
            }

            $categories = $categories->forget($providerCategory->category_id);
        });
        if ($categories->isNotEmpty()) {
            foreach ($categories as $cat_id => $item) {
                $provider->providerCategories()->create([
                    'category_id' => $cat_id,
                ]);
                $skills = array_unique($item['skills'] ?? []);
                if (! empty($skills)) {
                    $provider->categorySkills()->createMany(
                        array_map(static fn ($skill_id) => ['category_id' => $cat_id, 'skill_id' => $skill_id], $skills)
                    );
                }
            }
        }
    }
}
