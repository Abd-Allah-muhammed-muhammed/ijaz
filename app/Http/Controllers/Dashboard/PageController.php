<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PageRequest;
use App\Http\Resources\Dashboard\PageCollection;
use App\Http\Resources\Dashboard\PageResource;
use App\Models\Page;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class PageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show pages', only: ['index']),
            new Middleware('permission:create pages', only: ['create', 'store']),
            new Middleware('permission:edit pages', only: ['edit', 'update']),
            new Middleware('permission:delete pages', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Dashboard/Pages/Index', [
            'rows' => function () use ($request) {
                $row = Page::query()
                    ->with('translation')
                    ->when($request->search, function ($q, $search) {
                        $q->whereTranslationLike('title', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    })
                    ->latest()
                    ->paginate($request->integer('perPage', 10))
                    ->withQueryString();

                return PageCollection::make($row);
            },
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(PageRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['slug'] = \Str::slug($data['slug']);
            Page::create($data);
            DB::commit();

            return to_route('dashboard.pages.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Pages/Create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        return inertia('Dashboard/Pages/Edit', [
            'row' => PageResource::make($page),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['slug'] = \Str::slug($data['slug']);
            $page->update($data);
            DB::commit();

            return to_route('dashboard.pages.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(Page $page): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $page->delete();
            DB::commit();

            return to_route('dashboard.pages.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }

    }
}
