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
     * @param  array<string, array{content: string, is_public: bool}>  $updates
     */
    public function updateMany(array $updates): void;

    /**
     * @return array<string, string>
     */
    public function pluckPublicContentByKey(): array;
}
