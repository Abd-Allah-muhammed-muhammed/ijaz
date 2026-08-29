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
     * @param  array<string, string>  $contents  key => content
     */
    public function updateManyContents(array $contents): void;

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function pluckContentByKeys(array $keys): array;

    /**
     * @return array<string, string>
     */
    public function pluckPublicContentByKey(): array;
}
