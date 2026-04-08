<?php

namespace App\Enums\Utilities;

trait Stringable
{
    public function toString(): string
    {
        return trans(str($this->value)->lower()->value());
    }
}
