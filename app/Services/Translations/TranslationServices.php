<?php

namespace App\Services\Translations;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use SplFileInfo;

class TranslationServices
{
    /**
     * @throws FileNotFoundException
     */
    public function render(string $locale)
    {
        $translations = $this->getTranslationsFor($locale);
        $translations = json_encode($translations);

        return
          <<<HTML
         <script>window._translations = {$translations}</script>
      HTML;
    }

    /**
     * @throws FileNotFoundException
     */
    public function getTranslationsFor(string $locale)
    {
        return $this->getNoneCachedTranslationsFor($locale);

        return cache()->rememberForever('translations.'.$locale, function () use ($locale) {
            return $this->getNoneCachedTranslationsFor($locale);
        });
    }

    public function getNoneCachedTranslationsFor(string $locale): array
    {
        $php = [];
        $json = [];
        if (File::exists(lang_path($locale))) {
            $php = collect(File::allFiles(lang_path($locale)))
                ->filter(function (SplFileInfo $file) {
                    return $file->getExtension() === 'php';
                })
                ->flatMap(function (SplFileInfo $file) {
                    return Arr::dot(File::getRequire($file->getRealPath()), str_replace('.php', '', $file->getFilename()).'.');
                })
                ->toArray();
        }
        if (File::exists(lang_path("{$locale}.json"))) {
            $json = json_decode(File::get(lang_path("{$locale}.json")), true);
        }

        return [
            ...$php,
            ...$json,
        ];
    }
}
