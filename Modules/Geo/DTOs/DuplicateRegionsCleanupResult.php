<?php

namespace Modules\Geo\DTOs;

final readonly class DuplicateRegionsCleanupResult
{
    /**
     * @param  list<array{id: int, title_ar: string|null}>  $deleted
     */
    public function __construct(
        public int $regionCount,
        public int $cityCount,
        public array $deleted,
        public bool $dryRun,
    ) {}
}
