<?php

namespace App\Services\Translations;

class LocaleServices
{
    public function render(): string
    {
        $locales = $this->getSupportedLocals();
        $locales = json_encode($locales);

        return
          <<<HTML
         <script>window._locales = {$locales}</script>
      HTML;
    }

    public function getSupportedLocals()
    {
        return cache()->rememberForever('supported-locales', function () {
            return config('laravellocalization.supportedLocales');
        });
    }
}
