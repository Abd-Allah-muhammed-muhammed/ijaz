<?php

namespace Modules\Jobs\Enums;

enum JobTypeEnum: int
{
    case Governmental = 1;
    case Private = 2;

    public function label(): string
    {
        return trans(str($this->name)->lower()->value());
    }
}
