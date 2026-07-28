<?php

namespace App\Providers;

use App\Contracts\Account\AccountRepositoryInterface;
use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\Contracts\Admin\RoleRepositoryInterface;
use App\Contracts\Auth\AdminRepositoryInterface;
use App\Contracts\Auth\OtpRepositoryInterface;
use App\Contracts\Auth\ProviderRepositoryInterface;
use App\Contracts\Auth\UserRepositoryInterface;
use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Contracts\User\UserManagementRepositoryInterface;
use App\NotificationChannels\EventChannel;
use App\NotificationChannels\FirebaseChannel;
use App\Repositories\Account\AccountRepository;
use App\Repositories\Admin\AdminManagementRepository;
use App\Repositories\Admin\RoleRepository;
use App\Repositories\Auth\AdminRepository;
use App\Repositories\Auth\OtpRepository;
use App\Repositories\Auth\ProviderRepository;
use App\Repositories\Auth\UserRepository;
use App\Repositories\Provider\ProviderManagementRepository;
use App\Repositories\User\UserManagementRepository;
use App\Services\Chat\AppParticipantResolver;
use App\Support\Api\ApiVersionRegistry;
use App\Support\Api\ApiVersionResolverChain;
use App\Support\Api\ApiVersionService;
use App\Support\Api\Contracts\ApiVersionResolverStrategy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Chat\Contracts\ParticipantResolverInterface;
use Modules\Settings\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/arrays.php');

        $this->app->bind(
            AdminRepositoryInterface::class,
            AdminRepository::class,
        );

        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class,
        );

        $this->app->bind(
            AdminManagementRepositoryInterface::class,
            AdminManagementRepository::class,
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class,
        );

        $this->app->bind(
            ProviderRepositoryInterface::class,
            ProviderRepository::class,
        );

        $this->app->bind(
            UserManagementRepositoryInterface::class,
            UserManagementRepository::class,
        );

        $this->app->bind(
            ProviderManagementRepositoryInterface::class,
            ProviderManagementRepository::class,
        );

        $this->app->bind(
            AccountRepositoryInterface::class,
            AccountRepository::class,
        );

        $this->app->bind(
            OtpRepositoryInterface::class,
            OtpRepository::class,
        );

        $this->app->bind(
            ParticipantResolverInterface::class,
            AppParticipantResolver::class,
        );

        $this->app->singleton(ApiVersionRegistry::class);

        $this->app->singleton(ApiVersionResolverChain::class, function ($app): ApiVersionResolverChain {
            /** @var list<class-string<ApiVersionResolverStrategy>> $strategyClasses */
            $strategyClasses = config('api.negotiation.strategies', []);

            $strategies = [];

            foreach ($strategyClasses as $strategyClass) {
                $strategies[] = $app->make($strategyClass);
            }

            return new ApiVersionResolverChain(
                $strategies,
                $app->make(ApiVersionRegistry::class),
            );
        });

        $this->app->singleton(ApiVersionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->routes(static fn (Route $route): bool => str_starts_with($route->uri, 'api/v1/'))
            ->expose(ui: '/docs/api', document: '/docs/api.json')
            ->withDocumentTransformers(static function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer'));
            });

        Gate::define('viewApiDocs', static function ($user = null): bool {
            if (app()->environment(['local', 'testing'])) {
                return true;
            }

            $admin = Auth::guard('admin')->user();

            return (bool) ($admin?->root);
        });

        $this->app->singleton('settings', fn () => cache()->rememberForever('settings', fn () => Setting::pluck('content', 'key')));
        JsonResource::withoutWrapping();
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);
        Authenticate::redirectUsing(static function (Request $request) {
            if ($request->routeIs('dashboard.*')) {
                return route('dashboard.login.form');
            }

            if ($request->routeIs('provider.*')) {
                return route('provider.login');
            }

            return route('login');
        });
        RedirectIfAuthenticated::redirectUsing(static function (Request $request) {
            if ($request->routeIs('dashboard.*')) {
                return route('dashboard.home');
            }

            if ($request->routeIs('provider.*')) {
                return route('provider.home');
            }

            return route('/');
        });
        Notification::extend('firebase', static fn ($app) => $app->make(FirebaseChannel::class));
        Notification::extend('event', static fn ($app) => $app->make(EventChannel::class));
    }
}
