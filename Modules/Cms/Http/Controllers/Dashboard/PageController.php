<?php

namespace Modules\Cms\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Http\Requests\Dashboard\PageRequest;
use Modules\Cms\Http\Resources\Dashboard\PageCollection;
use Modules\Cms\Http\Resources\Dashboard\PageResource;
use Modules\Cms\Models\Page;
use Modules\Cms\Services\PageService;
use Throwable;

class PageController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PageService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show pages', only: ['index']),
            new Middleware('permission:create pages', only: ['create', 'store']),
            new Middleware('permission:edit pages', only: ['edit', 'update']),
            new Middleware('permission:delete pages', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Pages/Index', [
            'rows' => function () use ($request) {
                $rows = $this->service->index($request);

                return PageCollection::make($rows);
            },
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Pages/Create');
    }

    public function edit(Page $page): Response
    {
        $page = $this->service->show($page);

        return inertia('Dashboard/Pages/Edit', [
            'row' => PageResource::make($page),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(PageRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StorePageDTO::fromValidated($request->validated()));

            return to_route('dashboard.pages.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        try {
            $this->service->update($page, UpdatePageDTO::fromValidated($request->validated()));

            return to_route('dashboard.pages.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(Page $page): RedirectResponse
    {
        try {
            $this->service->destroy($page);

            return to_route('dashboard.pages.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }
}
