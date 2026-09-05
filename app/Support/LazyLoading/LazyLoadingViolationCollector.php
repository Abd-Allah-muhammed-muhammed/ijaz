<?php

namespace App\Support\LazyLoading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\Event;

/**
 * Collects lazy-loading violations without aborting the current request mid-flight,
 * so a single route hit can surface every offending relation access.
 *
 * Important Laravel quirk (framework Builder::hydrate): the instance flag
 * `$model->preventsLazyLoading` is only stamped when a query returns MORE THAN ONE
 * row. Single-record `find()`/`first()`/`show` responses therefore silently skip
 * Model::preventLazyLoading(). This collector forces the flag on every
 * `eloquent.retrieved` model so show endpoints and sparse lists are covered.
 */
final class LazyLoadingViolationCollector
{
    /** @var list<array{model: class-string, relation: string, uri: string|null, guard: string|null}> */
    private array $violations = [];

    private ?string $currentUri = null;

    private ?string $currentGuard = null;

    private bool $installed = false;

    /**
     * When true (default for sweeps), violations are recorded and the access is
     * allowed to continue so one request can surface multiple offenders.
     * Set false to restore throw-on-violation behaviour after the sweep.
     */
    private bool $collectOnly = true;

    private static bool $forceInstanceFlag = false;

    private static bool $retrievedHookRegistered = false;

    public function install(bool $collectOnly = true): void
    {
        if ($this->installed) {
            return;
        }

        $this->collectOnly = $collectOnly;
        Model::preventLazyLoading(true);
        self::$forceInstanceFlag = true;

        // Cover single-row hydrations that Laravel itself leaves unstamped.
        // Must use the wildcard dispatcher event — Model::retrieved() only binds the
        // base Model class name, so child models never receive that listener.
        if (! self::$retrievedHookRegistered) {
            Event::listen('eloquent.retrieved: *', static function (string $event, array $models): void {
                if (! self::$forceInstanceFlag) {
                    return;
                }

                foreach ($models as $model) {
                    if ($model instanceof Model) {
                        $model->preventsLazyLoading = true;
                    }
                }
            });
            self::$retrievedHookRegistered = true;
        }

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            $this->violations[] = [
                'model' => $model::class,
                'relation' => $relation,
                'uri' => $this->currentUri,
                'guard' => $this->currentGuard,
            ];

            if (! $this->collectOnly) {
                throw new LazyLoadingViolationException($model, $relation);
            }

            // Collect-only: swallow so the request continues and more relations can surface.
        });

        $this->installed = true;
    }

    public function setContext(?string $uri, ?string $guard): void
    {
        $this->currentUri = $uri;
        $this->currentGuard = $guard;
    }

    public function restore(): void
    {
        if (! $this->installed) {
            return;
        }

        Model::handleLazyLoadingViolationUsing(null);
        Model::preventLazyLoading(! app()->isProduction());
        self::$forceInstanceFlag = false;
        $this->installed = false;
    }

    /**
     * @return list<array{model: class-string, relation: string, uri: string|null, guard: string|null}>
     */
    public function all(): array
    {
        return $this->violations;
    }

    /**
     * Unique model+relation pairs (uri/guard collapsed).
     *
     * @return list<array{model: class-string, relation: string, uris: list<string>, guards: list<string>}>
     */
    public function uniqueByModelRelation(): array
    {
        $grouped = [];

        foreach ($this->violations as $violation) {
            $key = $violation['model'].'::'.$violation['relation'];
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'model' => $violation['model'],
                    'relation' => $violation['relation'],
                    'uris' => [],
                    'guards' => [],
                ];
            }

            if (is_string($violation['uri']) && $violation['uri'] !== '') {
                $grouped[$key]['uris'][$violation['uri']] = true;
            }

            if (is_string($violation['guard']) && $violation['guard'] !== '') {
                $grouped[$key]['guards'][$violation['guard']] = true;
            }
        }

        return array_values(array_map(static function (array $row): array {
            return [
                'model' => $row['model'],
                'relation' => $row['relation'],
                'uris' => array_keys($row['uris']),
                'guards' => array_keys($row['guards']),
            ];
        }, $grouped));
    }

    public function count(): int
    {
        return count($this->violations);
    }

    public function uniqueCount(): int
    {
        return count($this->uniqueByModelRelation());
    }

    public function reset(): void
    {
        $this->violations = [];
    }
}
