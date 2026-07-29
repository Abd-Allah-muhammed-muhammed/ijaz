<?php

namespace Modules\Geo\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Geo\DTOs\StoreNationalityDTO;
use Modules\Geo\DTOs\UpdateNationalityDTO;
use Modules\Geo\Exceptions\GeoException;
use Modules\Geo\Http\Requests\Dashboard\NationalityRequest;
use Modules\Geo\Http\Resources\Dashboard\NationalityCollection;
use Modules\Geo\Http\Resources\Dashboard\NationalityResource;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Services\NationalityService;
use Throwable;

class NationalityController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly NationalityService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show nationalities', only: ['index', 'show']),
            new Middleware('permission:create nationalities', only: ['create', 'store']),
            new Middleware('permission:edit nationalities', only: ['edit', 'update']),
            new Middleware('permission:delete nationalities', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->index($request);

        return inertia('Dashboard/Nationalities/Index', [
            'params' => $request->all() ?: [],
            'rows' => NationalityCollection::make($rows),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Nationalities/Create');
    }

    /**
     * @throws Throwable
     */
    public function store(NationalityRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreNationalityDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.nationalities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(Nationality $nationality): Response
    {
        return inertia('Dashboard/Nationalities/Edit', [
            'row' => NationalityResource::make($nationality),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(NationalityRequest $request, Nationality $nationality): RedirectResponse
    {
        try {
            $this->service->update($nationality, UpdateNationalityDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.nationalities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(Nationality $nationality): RedirectResponse
    {
        try {
            $this->service->destroy($nationality);

            return redirect()->route('dashboard.nationalities.index')->with('success', __('data deleted successfully'));
        } catch (GeoException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }
}
