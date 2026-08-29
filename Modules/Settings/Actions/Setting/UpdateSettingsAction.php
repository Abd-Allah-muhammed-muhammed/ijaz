<?php

namespace Modules\Settings\Actions\Setting;

use App\Models\Admin;
use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Contracts\Repositories\SettingHistoryRepositoryInterface;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\DTOs\UpdateSettingsDTO;

class UpdateSettingsAction
{
    public function __construct(
        private readonly SettingRepositoryInterface $repository,
        private readonly SettingHistoryRepositoryInterface $historyRepository,
    ) {}

    public function handle(UpdateSettingsDTO $dto, ?Admin $admin = null): void
    {
        DB::transaction(function () use ($dto, $admin): void {
            $existing = $this->repository->pluckContentByKeys(array_keys($dto->values));
            $contentUpdates = [];
            $actorName = $admin?->name;

            foreach ($dto->values as $key => $newContent) {
                if (! array_key_exists($key, $existing)) {
                    continue;
                }

                $oldContent = $existing[$key];
                $normalizedNew = (string) $newContent;

                if ($oldContent === $normalizedNew) {
                    continue;
                }

                $contentUpdates[$key] = $normalizedNew;

                $this->historyRepository->create([
                    'key' => $key,
                    'old_content' => $oldContent,
                    'new_content' => $normalizedNew,
                    'admin_id' => $admin?->id,
                    'actor_name' => $actorName,
                ]);
            }

            if ($contentUpdates !== []) {
                $this->repository->updateManyContents($contentUpdates);
            }
        });

        cache()->forget('settings');
        app()->forgetInstance('settings');
        LookupCache::forget('settings:public');
    }
}
