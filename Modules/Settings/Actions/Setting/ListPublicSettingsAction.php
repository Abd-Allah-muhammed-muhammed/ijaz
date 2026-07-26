<?php

namespace Modules\Settings\Actions\Setting;

/**
 * Filters the cached settings bag for the public catalog API.
 *
 * Only keys listed in config('settings.public_keys') are returned. The
 * singleton itself is unchanged — consumers that call app('settings')->get()
 * still see every key.
 */
class ListPublicSettingsAction
{
    /**
     * @return array<string, string>
     */
    public function handle(): array
    {
        /** @var list<string> $allowlist */
        $allowlist = config('settings.public_keys', []);
        $all = app('settings')->toArray();

        return array_intersect_key($all, array_flip($allowlist));
    }
}
