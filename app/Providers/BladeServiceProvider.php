<?php

namespace App\Providers;

use App\Services\Translations\LocaleServices;
use App\Services\Translations\TranslationServices;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('translation', function ($expression) {
                $locale = $this->app->getLocale();
                dd($locale);

                return $this->app->make(TranslationServices::class)->render($locale);
            });
            $bladeCompiler->directive('locales', function () {
                return $this->app->make(LocaleServices::class)->render();
            });
        });
    }
}
