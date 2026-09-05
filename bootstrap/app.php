<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\AuthenticateBroadcasting;
use App\Http\Middleware\EnsureAcceptJsonMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LocalizationMiddleware;
use App\Support\Api\ApiErrorResponse;
use App\Support\Api\ApiVersionRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Inertia\ExceptionResponse;
use Inertia\Support\Header as InertiaHeader;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use MMAE\ApiResponse\Configurations\Response as ApiResponseConfig;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
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
            'user.active' => EnsureUserIsActive::class,
            'auth.broadcasting' => AuthenticateBroadcasting::class,
        ]);

        // Guard + live status check for User Sanctum API (belt already revokes on ban/delete).
        $middleware->group('user-api', [
            'auth:user-api',
            'user.active',
        ]);
    })
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        // Prefer AuthenticateBroadcasting over auth:admin,provider — the latter
        // always resolves Admin first when both dashboard sessions exist.
        ['middleware' => ['web', 'auth.broadcasting']],
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('opportunities:expire')->hourly();
        $schedule->command('orders:settle-completed')->hourly()->withoutOverlapping();
        $schedule->command('orders:alert-unsettled')->hourly();
        $schedule->command('orders:expire-pending-offers')->dailyAt('00:30');
        $schedule->command('guarantor:check-overdue')->dailyAt('00:00');
        $schedule->command('auth:prune-expired-otp-sessions')->hourly();
        $schedule->command('auth:prune-provider-registration-uploads')->hourly();
        // Telescope is toggle-based (off by default); prune daily with 48h retention.
        $schedule->command('telescope:prune --hours=48')->daily();
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

                return ApiErrorResponse::failure(
                    message: __($key),
                    statusCode: 404,
                );
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

        $exceptions->renderable(function (ApiException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $e->render();
            }
        });

        $renderAccessDeniedJson = function (Request $request, string $message, int $statusCode = 403) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiErrorResponse::failure(
                    message: $message !== '' ? $message : __('This action is unauthorized.'),
                    statusCode: $statusCode,
                );
            }
        };

        /*
         * Policy denials ($this->authorize / Response::deny) become AuthorizationException,
         * then Handler::prepareException() maps them to AccessDeniedHttpException (403) or
         * HttpException (custom status) *before* render callbacks run — register those types.
         */
        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) use ($renderAccessDeniedJson) {
            return $renderAccessDeniedJson($request, $e->getMessage());
        });

        $exceptions->renderable(function (SymfonyHttpException $e, Request $request) use ($renderAccessDeniedJson) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            $message = $e->getMessage();
            $previous = $e->getPrevious();

            if ($previous instanceof AuthorizationException && $previous->getMessage() !== '') {
                $message = $previous->getMessage();
            }

            return $renderAccessDeniedJson($request, $message);
        });

        $exceptions->renderable(function (AuthorizationException $e, Request $request) use ($renderAccessDeniedJson) {
            return $renderAccessDeniedJson($request, $e->getMessage(), $e->status() ?? 403);
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiErrorResponse::failure(
                    message: $e->getMessage() ?: __('Unauthenticated.'),
                    statusCode: 401,
                );
            }
        });

        /*
         * Safety net only: PHP post_max_size / CONTENT_LENGTH (ValidatePostSize).
         * The real UX-facing upload limit is Laravel validation (max:5120 ≈ 5MB).
         * PHP ini should be generous (e.g. 64M) so normal users never hit this path;
         * when they do (abuse / misconfigured server), return the same MMAE
         * validation envelope so upload UIs can toast cleanly — never a raw page.
         */
        $exceptions->renderable(function (PostTooLargeException $e, Request $request) {
            $message = __('One of your files exceeds the upload limit.');

            if ($request->header(InertiaHeader::INERTIA) && ! $request->expectsJson()) {
                return redirect()
                    ->back()
                    ->withErrors(['files' => $message]);
            }

            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'errors' => [
                        'files' => [$message],
                    ],
                    'message' => ApiResponseConfig::$VALIDATION_FAILED_MESSAGE,
                    'token' => '',
                ], ApiResponseConfig::$VALIDATION_FAILED_STATUS);
            }

            return redirect()
                ->back()
                ->withErrors(['files' => $message]);
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
