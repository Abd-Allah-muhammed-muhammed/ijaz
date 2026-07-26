<?php

namespace Modules\Settings\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Models\Setting;

class SettingRepository implements SettingRepositoryInterface
{
    /**
     * @return Collection<int, Setting>
     */
    public function all(): Collection
    {
        return Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, Setting>>
     */
    public function allGroupedByGroup(): Collection
    {
        return $this->all()->groupBy(fn (Setting $setting): string => $setting->group ?? 'general');
    }

    public function findByKey(string $key): ?Setting
    {
        return Setting::query()->where('key', $key)->first();
    }

    public function updateByKey(string $key, string $content): Setting
    {
        $setting = Setting::query()->where('key', $key)->firstOrFail();
        $setting->update(['content' => $content]);

        return $setting->fresh() ?? $setting;
    }

    /**
     * @param  array<string, string>  $keyValuePairs
     */
    public function updateMany(array $keyValuePairs): void
    {
        DB::transaction(function () use ($keyValuePairs): void {
            foreach ($keyValuePairs as $key => $content) {
                Setting::query()
                    ->where('key', $key)
                    ->update(['content' => (string) $content]);
            }
        });
    }
}
