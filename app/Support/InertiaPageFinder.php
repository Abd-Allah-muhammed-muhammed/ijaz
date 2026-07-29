<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Resolves Inertia page components after the apps/{admin,provider,web} + shared split.
 * Backend component names stay prefixed; physical files drop the prefix.
 */
final class InertiaPageFinder
{
    public function find(string $view): string
    {
        foreach (config('inertia.pages.extensions', ['tsx', 'ts', 'jsx', 'js']) as $extension) {
            $path = InertiaPagePath::absolute($view, $extension);

            if (is_file($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException("Inertia page component [{$view}] not found.");
    }
}
