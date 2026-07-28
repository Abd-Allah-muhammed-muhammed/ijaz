<?php

namespace Modules\Chat\Contracts\Repositories;

use Modules\Chat\Models\System;

interface SystemRepositoryInterface
{
    public function findOrCreateDefault(): System;
}
