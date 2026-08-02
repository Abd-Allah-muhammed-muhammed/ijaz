<?php

namespace App\Http\Middleware;

use App\Enums\FrontendShell;
use App\Http\Resources\Dashboard\AdminResource;
use App\Http\Resources\Dashboard\ProviderResource;
use App\Models\Admin;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Services\PaymentService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        $success = session('success');
        $error = session('error');
        $shell = FrontendShell::fromRequest($request);

        View::share('appShell', $shell->value);

        $paymentDriver = app(PaymentService::class)->getDefaultDriver();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->getAuth($request),
                'permissions' => $this->getPermissions($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'app' => [
                'locale' => app()->getLocale(),
                'shell' => $shell->value,
            ],
            'flash' => [
                'success' => $this->makeMessage($success),
                'error' => $this->makeMessage($error),
            ],
            'payment' => [
                'driver' => $paymentDriver,
                // True for any recognized gateway including Testing (QA simulation).
                // False only when config is empty/invalid and does not map to PaymentDriverEnum.
                'online_enabled' => PaymentDriverEnum::tryFrom($paymentDriver) !== null,
            ],
        ];
    }

    private function getAuth(Request $request)
    {
        $auth = $request->user();
        if (is_null($auth)) {
            return null;
        }

        return match (get_class($auth)) {
            Provider::class => ProviderResource::make($auth->load('categories')),
            Admin::class => AdminResource::make($auth->load('roles', 'permissions'))->toArray($request),
            default => null,
        };
    }

    private function makeMessage(?string $message): ?array
    {
        if ($message) {
            return [
                'id' => Str::uuid(),
                'content' => $message,
            ];
        }

        return null;
    }

    protected function getPermissions(Request $request): array
    {
        $user = $request->user();
        if (is_null($user)) {
            return [];
        }
        if (! method_exists($user, 'getAllPermissions')) {
            return [];
        }

        return $request->user()->getAllPermissions()->pluck('name')->toArray();
    }
}
