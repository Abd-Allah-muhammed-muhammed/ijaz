<?php

namespace Modules\Catalog\Actions\Specialization;

use Modules\Catalog\Models\Specialization;

class ShowSpecializationAction
{
    public function handle(Specialization $specialization): Specialization
    {
        return $specialization
            ->loadCount('children')
            ->load([
                'translation',
                'children.translation',
            ]);
    }
}
