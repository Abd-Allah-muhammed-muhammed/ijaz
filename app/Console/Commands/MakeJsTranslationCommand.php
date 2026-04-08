<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

class MakeJsTranslationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:js-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @throws JsonException
     */
    public function handle(): void
    {
        $input = base_path('lang');
        $output = resource_path('js/lang');
        if (! is_dir($output) && ! mkdir($output, 0777, true) && ! is_dir($output)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $output));
        }
        collect(File::glob($input.DIRECTORY_SEPARATOR.'*.json'))
            ->each(function ($p) use ($input, $output) {
                $path = str($p)
                    ->after($input.DIRECTORY_SEPARATOR)
                    ->replace(DIRECTORY_SEPARATOR, '.')
                    ->toString();
                [$name] = explode('.', $path, 2);
                $fullPath = $output.DIRECTORY_SEPARATOR.$name.'.json';
                if (! file_exists($fullPath)) {
                    touch($fullPath);
                }

                $content = json_decode(file_get_contents($p), true, 512, JSON_THROW_ON_ERROR);
                $content = $this->transform($content);

                file_put_contents($fullPath, json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            });
        collect(File::allDirectories($input))->each(function ($p) use ($input, $output) {
            $file = str($p)
                ->after($input.DIRECTORY_SEPARATOR)
                ->toString();

            $outputFilePath = $output.DIRECTORY_SEPARATOR.$file.'.json';
            if (! file_exists($outputFilePath)) {
                touch($outputFilePath);
            }

            collect(File::glob($p.DIRECTORY_SEPARATOR.'*.php'))
                ->each(function ($filePath) use ($input, $output, $outputFilePath, $file) {
                    $name = str($filePath)
                        ->after($input.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR)
                        ->replace('.php', '')
                        ->toString();

                    $content = file_get_contents($outputFilePath);
                    if (empty($content)) {
                        $content = json_encode(
                            [
                                $name => $this->transform(require $filePath),
                            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                        );
                        file_put_contents($outputFilePath, $content);
                    } else {
                        $contentDecoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                        $data = [
                            ...$contentDecoded,
                            $name => $this->transform(require $filePath),
                        ];
                        file_put_contents($outputFilePath, json_encode(
                            $data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                        ));
                    }
                });
        });
    }

    private function transform(array|string $content): array|string
    {
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $content[$key] = $this->transform($value);
            }

            return $content;
        }

        return preg_replace('/:(\w+)/', '{{$1}}', $content);
    }
}
