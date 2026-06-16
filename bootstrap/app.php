<?php

use App\Http\Middleware\EnsureAcceptJsonMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/dashboard.php'));
            Route::middleware('web')->group(base_path('routes/provider.php'));

            Route::middleware(config('modules.routes.api.middleware', ['api']))
                ->prefix(config('modules.routes.api.prefix', 'api/v1'))
                ->group(static function () {
                    Route::group([], base_path('routes/Api/V1/user.php'));
                    Route::group([], base_path('routes/Api/V1/catalog.php'));

                    if (! config('modules.routes.enabled', false)) {
                        return;
                    }

                    $apiRoutesFile = config('modules.routes.api.file', 'Routes/V1/api.php');
                    $namePrefix = config('modules.routes.api.name', 'api.v1.');

                    foreach (Module::allEnabled() as $module) {
                        $moduleName = $module->getLowerName();
                        $routesPath = module_path($module->getName(), $apiRoutesFile);

                        if (! is_file($routesPath)) {
                            continue;
                        }

                        Route::name($namePrefix.$moduleName.'.')->group($routesPath);
                    }
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
            ]
        );
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
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('opportunities:expire')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $renderModelNotFound = function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                $modelMap = [
                    Opportunity::class => 'opportunity.not_found',
                    OpportunityOffer::class => 'opportunity.offer_not_found',
                    OpportunityComment::class => 'opportunity.comment_not_found',
                ];

                $key = $modelMap[$e->getModel()] ?? 'errors.not_found';

                return response()->json([
                    'success' => false,
                    'message' => __($key),
                    'data' => [],
                    'errors' => [],
                ], 404);
            }
        };

        $exceptions->renderable(function (ModelNotFoundException $e, $request) use ($renderModelNotFound) {
            return $renderModelNotFound($e, $request);
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) use ($renderModelNotFound) {
            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return $renderModelNotFound($e->getPrevious(), $request);
            }
        });
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
