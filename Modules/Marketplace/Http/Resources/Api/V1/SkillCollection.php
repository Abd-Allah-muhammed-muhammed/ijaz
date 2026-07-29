<?php

namespace Modules\Marketplace\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;
use Modules\Marketplace\Models\Skill;

/** @see Skill */
class SkillCollection extends BaseCollection
{
    public $collects = SkillResource::class;
}
