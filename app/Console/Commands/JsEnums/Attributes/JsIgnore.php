<?php

namespace App\Console\Commands\JsEnums\Attributes;

#[\Attribute]
class JsIgnore
{
    public function __construct(public ?array $envs = null) {}
}
