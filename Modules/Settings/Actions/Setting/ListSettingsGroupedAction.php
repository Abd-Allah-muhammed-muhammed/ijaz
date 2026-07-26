<?php

namespace Modules\Settings\Actions\Setting;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Models\Setting;

class ListSettingsGroupedAction
{
    public function __construct(
        private readonly SettingRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<string, EloquentCollection<int, Setting>>
     */
    public function handle(): Collection
    {
        $grouped = $this->repository->allGroupedByGroup();
        $ordered = collect();

        foreach (Setting::GROUPS as $group) {
            /** @var EloquentCollection<int, Setting> $bucket */
            $bucket = $grouped->get($group, new EloquentCollection);
            $ordered->put($group, $bucket);
        }

        foreach ($grouped as $group => $settings) {
            if (! $ordered->has($group)) {
                $ordered->put($group, $settings);
            }
        }

        return $ordered;
    }
}
