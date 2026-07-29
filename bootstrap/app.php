<?php

use App\Http\Middleware\EnsureAcceptJsonMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LocalizationMiddleware;
use App\Support\Api\ApiVersionRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Inertia\ExceptionResponse;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
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

            $version = app(ApiVersionRegistry::class)->default();

            Route::middleware(['api'])
                ->prefix($version->prefix)
                ->group(static function () use ($version) {
                    Route::group([], base_path('routes/api/'.$version->folder.'/auth.php'));
                    Route::group([], base_path('routes/api/'.$version->folder.'/platform.php'));
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
        $schedule->command('guarantor:check-overdue')->dailyAt('00:00');
        $schedule->command('auth:prune-expired-otp-sessions')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $renderModelNotFound = function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                $modelMap = [
                    Opportunity::class => 'opportunity.not_found',
                    OpportunityOffer::class => 'opportunity.offer_not_found',
                    OpportunityComment::class => 'opportunity.comment_not_found',
                    GuarantorRequest::class => 'guarantor.not_found',
                    GuarantorInstallment::class => 'guarantor.installment_not_found',
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

        $exceptions->renderable(function (GuarantorException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $e->render();
            }
        });

        /*
         * Inertia v3 error pages: use ExceptionResponse + withSharedData so 404s
         * (which skip HandleInertiaRequests) still get shared props / root view.
         * API/JSON and local debug (Ignition) are left untouched.
         */
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            if (app()->environment(['local', 'testing']) && config('app.debug')) {
                return $response;
            }

            $statusCode = $response->getStatusCode();

            if (! in_array($statusCode, [401, 403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            $segment = $request->segment(1);
            $supportedLocales = LaravelLocalization::getSupportedLanguagesKeys();

            if (is_string($segment) && in_array($segment, $supportedLocales, true)) {
                app()->setLocale($segment);
            }

            return (new ExceptionResponse(
                $e,
                $request,
                $response,
                app(Router::class),
                app(HttpKernel::class),
            ))
                ->render('Errors/ErrorPage', [
                    'status' => $statusCode,
                    'title' => __('error_'.$statusCode.'_title'),
                    'message' => __('error_'.$statusCode.'_message'),
                ])
                ->usingMiddleware(HandleInertiaRequests::class)
                ->withSharedData()
                ->toResponse($request);
        });
    })->create();
