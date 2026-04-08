<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;
use App\Models\Skill;

/** @see Skill */
class SkillCollection extends BaseCollection
{
    public $collects = SkillResource::class;
}
