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
            $bladeCompiler->directive('translation', function () {
                return '<?php echo app(\\'.TranslationServices::class.'::class)->render(app()->getLocale()); ?>';
            });
            $bladeCompiler->directive('locales', function () {
                return '<?php echo app(\\'.LocaleServices::class.'::class)->render(); ?>';
            });
        });
    }
}
