<?php

namespace App\Console\Commands;

use App\Console\Commands\JsEnums\Attributes\JsClass;
use App\Console\Commands\JsEnums\Attributes\JsFunction;
use App\Console\Commands\JsEnums\Attributes\JsIgnore;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use JsonException;
use Modules\Payment\Enums\PaymentDriverEnum;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use RuntimeException;

class MakeJsEnumsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:js-enums';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $path = base_path('app/Enums/');
        $output = resource_path('js/Enums');
        //    $this->makeJsFile($output, 'index', collect([PaymentDriverEnum::class]));
        //    return;
        if (! is_dir($output) && ! mkdir($output, 0777, true) && ! is_dir($output)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $output));
        }
        collect(array_merge(glob($path.'*/*Enum.php'), glob($path.'*Enum.php')))
            ->map(fn ($file) => str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file))
            ->map(fn ($file) => str($file)
                ->replace([base_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'], ['', '\\', ''])
                ->ucfirst()
                ->toString()
            )
            ->groupBy(fn ($file) => str($file)->beforeLast('\\')->afterLast('\\')->toString())
            ->each(fn ($files, $key) => $this->makeJsFile($output, $key, $files));

    }

    protected function makeJsFile(string $path, string $file, Collection $enums): void
    {
        $content = $this->getJsContent($enums);
        $path = "$path/$file.ts";
        if (! file_exists($path)) {
            touch($path);
        }
        file_put_contents($path, $content);
    }

    protected function getJsContent(Collection $enums): string
    {
        return $enums->map(function ($enum) {
            $ref = new ReflectionEnum($enum);
            $enumValues = collect($ref->getCases())
                ->filter(function (ReflectionEnumBackedCase $enum) {
                    $attrs = $enum->getAttributes(JsIgnore::class);
                    if (empty($attrs)) {
                        return true;
                    }
                    /** @var JsIgnore $obj */
                    $obj = $attrs[0]->newInstance();
                    if (is_null($obj->envs)) {
                        return false;
                    }

                    return ! in_array(app()->environment(), $obj->envs, true);
                })
                ->mapWithKeys(function (ReflectionEnumBackedCase $enumBackedCase) {
                    return [str($enumBackedCase->name)->toString() => $enumBackedCase->getBackingValue()];
                });

            $classAttr = $ref->getAttributes(JsClass::class);
            $classObj = null;
            if ($classAttr) {
                $classObj = $classAttr[0]->newInstance();
            }
            $fns = [];
            foreach ($ref->getMethods() as $method) {
                $att = $method->getAttributes(JsFunction::class);
                if (empty($att)) {
                    continue;
                }
                /** @var JsFunction $attrInstance */
                $attrInstance = $att[0]->newInstance();
                $args = implode(', ', $attrInstance->arguments);
                $body = $attrInstance->getBody();
                $returnType = null;
                if ($attrInstance->ts) {
                    $returnTypeObj = $method->getReturnType();
                    if (! $returnTypeObj) {
                        $returnType = ': any';
                    } else {
                        if (! $returnTypeObj->isBuiltin()) {
                            throw new RuntimeException("Return type of {$enum}::{$method->getName()} is not a built-in type");
                        }
                        $returnType = match ($returnTypeObj->getName()) {
                            'int', 'float' => ': number',
                            'bool' => ': boolean',
                            'string' => ': string',
                            default => ': any'
                        };
                    }
                    if ($returnTypeObj->allowsNull()) {
                        $returnType .= '|null';
                    }
                }
                $fns[$attrInstance->name] = "{$attrInstance->name}({$args}){$returnType} {".PHP_EOL."\t\t".trim($body).PHP_EOL."\t}";
            }

            return $this->makeJson(str($enum)->afterLast('\\')->toString(), $enumValues->toArray(), $fns, $classObj);
        })->implode("\n");
    }

    /**
     * @throws JsonException
     */
    protected function makeJson(string $name, array $enums, array $fns = [], ?JsClass $jsClass = null): string
    {

        //    $js = "export const {$name} =" . json_encode($enums, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";";
        $js = '';
        if ($jsClass) {
            $js .= $jsClass->getClass($fns);
            $js .= "export const {$name} = {".PHP_EOL.implode(",\n", array_map(static fn ($k, $v) => "  $k: new {$jsClass->name}('{$k}','{$v}')", array_keys($enums), array_values($enums))).','.PHP_EOL;

            return $js.'} as const;';
        }
        $js .= "export const {$name} = {".PHP_EOL.implode(",\n", array_map(static fn ($k, $v) => "  $k: ".(is_string($v) ? '"'.$v.'"' : $v), array_keys($enums), array_values($enums))).','.PHP_EOL;
        if (empty($fns)) {
            return $js.'} as const;';
        }
        $js .= implode(",\n", array_map(static fn ($v) => '  '.$v, array_values($fns))).PHP_EOL.'} as const;';

        return $js;
        //    return "export const {$name} =" . json_encode($enums, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ";";
    }
}
