<?php

namespace Modules\Settings\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Settings\Models\Setting;

interface SettingRepositoryInterface
{
    /**
     * @return Collection<int, Setting>
     */
    public function all(): Collection;

    /**
     * @return Collection<string, Collection<int, Setting>>
     */
    public function allGroupedByGroup(): Collection;

    public function findByKey(string $key): ?Setting;

    public function updateByKey(string $key, string $content): Setting;

    /**
     * @param  array<string, string>  $keyValuePairs
     */
    public function updateMany(array $keyValuePairs): void;
}
