<?php

namespace Modules\Settings\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Settings\DTOs\UpdateSettingsDTO;
use Modules\Settings\Http\Requests\Dashboard\UpdateSettingsRequest;
use Modules\Settings\Http\Resources\Dashboard\SettingResource;
use Modules\Settings\Services\SettingService;
use Throwable;

class SettingController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SettingService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show settings', only: ['index']),
            new Middleware('permission:edit settings', only: ['update']),
        ];
    }

    public function index(Request $request): Response
    {
        $grouped = $this->service->indexGrouped();

        return inertia('Dashboard/Settings/Index', [
            'groups' => $grouped->map(fn ($settings) => SettingResource::collection($settings)->resolve()),
            'groupOrder' => config('settings.groups', []),
            'prams' => $request->only('tab') ?: [],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        try {
            $this->service->update(UpdateSettingsDTO::fromValidated($request->validated()));

            return redirect()
                ->route('dashboard.settings.index', array_filter([
                    'tab' => $request->validated('group'),
                ]))
                ->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
