<?php

namespace App\Support\LazyLoading;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use Modules\Orders\Listeners\NotifyOrderPaymentCompleted;
use Modules\Orders\Listeners\NotifyOrderPaymentFailed;
use ReflectionClass;

/**
 * Discovers queued listeners/jobs and other non-HTTP processors for lazy-load probes.
 */
final class NonHttpLazyLoadingCatalog
{
    /**
     * Known high-risk queued listeners that touch nested Eloquent graphs.
     *
     * @return list<class-string>
     */
    public static function queuedOrderPaymentListeners(): array
    {
        return [
            NotifyOrderPaymentCompleted::class,
            NotifyOrderPaymentFailed::class,
        ];
    }

    /**
     * @return list<class-string>
     */
    public static function shouldQueueClassesUnder(string $directory, string $namespacePrefix): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $classes = [];
        foreach (File::allFiles($directory) as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = rtrim($namespacePrefix, '\\').'\\'.$relative;
            if (! class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->isInstantiable() && $reflection->implementsInterface(ShouldQueue::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
