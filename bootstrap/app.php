<?php

use App\Http\Middleware\EnsureAcceptJsonMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/dashboard.php'));
            Route::middleware('web')->group(base_path('routes/provider.php'));

            Route::group(['prefix' => 'api', 'middleware' => 'api'], static function () {
                Route::prefix('v1')->group(function () {
                    Route::group([], base_path('routes/Api/V1/user.php'));
                    Route::group([], base_path('routes/Api/V1/catalog.php'));
                });
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(
            append: [
                'throttle:60,1',
            ],
            prepend: [
                EnsureAcceptJsonMiddleware::class,
                LocalizationMiddleware::class,
            ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth:admin,provider']],
    )
    ->withExceptions(function (Exceptions $exceptions) {
        //    if (request()->is('api/*') && app()->isProduction()) {
        //      $exceptions->render(function (Exception $exception) {
        //        return (new class implements Responsable {
        //          use HasApiResponse;
        //
        //          protected ?Exception $exception = null;
        //
        //          public function setException(Exception $exception)
        //          {
        //            $this->exception = $exception;
        //            return $this;
        //          }
        //
        //          protected function findCode(): int
        //          {
        //            if (empty($this->exception)) {
        //              return 500;
        //            }
        //
        //            $code = (int)$this->exception->getCode();
        //            if ($code >= 200 && $code < 600) {
        //              return $code;
        //            }
        //
        //            return match (get_class($this->exception)) {
        //              Illuminate\Auth\AuthenticationException::class => 401,
        //              Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class => 403,
        //              default => 500,
        //            };
        //          }
        //
        //          public function toResponse($request)
        //          {
        //            return $this->failedMessageResponse(
        //              $this->exception->getMessage(),
        //              $this->findCode()
        //            );
        //          }
        //        })
        //          ->setException($exception);
        //      });
        //    }
        //    $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response) {
        //        return match ($response->getStatusCode()) {
        //          401 => inertia('Errors/Unauthorized', []),
        //          403 => inertia('Errors/Forbidden', []),
        //          404 => inertia('Errors/NotFound', []),
        //          500 => inertia('Errors/ServerError', []),
        //          default => $response,
        //        };
        //    });
    })->create();
