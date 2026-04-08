<?php

namespace App\Console\Commands\JsEnums\Attributes;

#[\Attribute]
class JsClass
{
    public function __construct(public string $name, public bool $ts = false) {}

    public function getClass(array $fns): string
    {
        $accessModifier = $this->ts ? 'public ' : null;
        $pram = $this->ts ? ' :string' : null;
        $js = 'class '.$this->name.' {'.PHP_EOL;
        $js .= "\t{$accessModifier} name{$pram};".PHP_EOL;
        $js .= "\t{$accessModifier} value{$pram};".PHP_EOL;
        $js .= "\tconstructor(name{$pram}, value{$pram}) {".PHP_EOL;
        $js .= "\t\tthis.name = name;".PHP_EOL;
        $js .= "\t\tthis.value = value;".PHP_EOL;
        $js .= "\t}".PHP_EOL;
        foreach ($fns as $fn) {
            $js .= "\t".$fn.PHP_EOL;
        }
        $js .= '}'.PHP_EOL;

        return $js;
    }
}
