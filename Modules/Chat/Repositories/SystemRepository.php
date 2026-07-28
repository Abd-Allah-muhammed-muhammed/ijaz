<?php

namespace Modules\Chat\Repositories;

use Modules\Chat\Contracts\Repositories\SystemRepositoryInterface;
use Modules\Chat\Models\System;

class SystemRepository implements SystemRepositoryInterface
{
    public function findOrCreateDefault(): System
    {
        return System::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'System', 'online' => false],
        );
    }
}
