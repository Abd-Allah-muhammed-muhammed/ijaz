<?php

namespace Modules\Marketplace\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Marketplace\DTOs\StoreSkillDTO;
use Modules\Marketplace\DTOs\UpdateSkillDTO;
use Modules\Marketplace\Http\Requests\Dashboard\SkillRequest;
use Modules\Marketplace\Http\Resources\Dashboard\CategoryResource;
use Modules\Marketplace\Http\Resources\Dashboard\SkillCollection;
use Modules\Marketplace\Http\Resources\Dashboard\SkillResource;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;
use Modules\Marketplace\Services\CategoryService;
use Modules\Marketplace\Services\SkillService;
use Throwable;

class SkillController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SkillService $service,
        private readonly CategoryService $categoryService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show skills', only: ['index', 'show']),
            new Middleware('permission:create skills', only: ['create', 'store']),
            new Middleware('permission:edit skills', only: ['edit', 'update']),
            new Middleware('permission:delete skills', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Skills/Index', [
            'prams' => $request->all() ?: [],
            'rows' => SkillCollection::make($this->service->index($request)),
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Skills/Create', [
            'categories' => CategoryResource::collection($this->categoryService->getLeafCategories()),
        ]);
    }

    public function store(SkillRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreSkillDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.skills.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function edit(Skill $skill): Response
    {
        $skill = $this->service->show($skill);

        return inertia('Dashboard/Skills/Edit', [
            'row' => SkillResource::make($skill),
            'categories' => CategoryResource::collection(
                Category::with(['translations'])->get()
            ),
        ]);
    }

    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        try {
            $this->service->update($skill, UpdateSkillDTO::fromValidated($request->validated()));

            return redirect()->route('dashboard.skills.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $this->service->destroy($skill);

        return redirect()->back()->with('success', __('data deleted successfully'));
    }
}
