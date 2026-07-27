<?php

namespace Modules\Settings\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Enums\SettingGroupEnum;
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
        return $this->all()->groupBy(
            fn (Setting $setting): string => $setting->group?->value ?? SettingGroupEnum::General->value
        );
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
     * @param  array<string, array{content: string, is_public: bool}>  $updates
     */
    public function updateMany(array $updates): void
    {
        DB::transaction(function () use ($updates): void {
            foreach ($updates as $key => $payload) {
                Setting::query()
                    ->where('key', $key)
                    ->update([
                        'content' => (string) $payload['content'],
                        'is_public' => (bool) $payload['is_public'],
                    ]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function pluckPublicContentByKey(): array
    {
        return Setting::query()
            ->where('is_public', true)
            ->pluck('content', 'key')
            ->all();
    }
}
