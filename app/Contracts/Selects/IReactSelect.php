<?php

namespace App\Contracts\Selects;

interface IReactSelect
{
    public function getLabel(): string;

    public function getValue(): string;
}
