<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SkillRequest;
use App\Http\Resources\Dashboard\CategoryResource;
use App\Http\Resources\Dashboard\SkillCollection;
use App\Http\Resources\Dashboard\SkillResource;
use App\Models\Category;
use App\Models\Skill;
use App\Services\Normalize\Normalize;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class SkillController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show skills', only: ['index', 'show']),
            new Middleware('permission:create skills', only: ['create', 'store']),
            new Middleware('permission:edit skills', only: ['edit', 'update']),
            new Middleware('permission:delete skills', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $skills = Skill::with(['translation', 'category'])
            ->when($request->integer('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Skills/Index', [
            'prams' => $request->all() ?: [],
            'rows' => SkillCollection::make($skills),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SkillRequest $request)
    {
        DB::beginTransaction();
        try {
            $skill = Skill::create($request->validated());
            DB::commit();

            return redirect()->route('dashboard.skills.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Skills/Create', [
            'categories' => CategoryResource::collection(
                Category::with(['translation'])
                    ->whereDoesntHave('children')
                    ->get()
            ),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Skill $skill)
    {
        $skill->load(['translations', 'category']);

        return inertia('Dashboard/Skills/Edit', [
            'row' => SkillResource::make($skill),
            'categories' => CategoryResource::collection(
                CategoryResource::collection(
                    Category::with(['translations'])
                        ->get()
                )
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SkillRequest $request, Skill $skill)
    {
        DB::beginTransaction();
        try {
            $skill->update($request->validated());
            DB::commit();

            return redirect()->route('dashboard.skills.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Skill $skill)
    {
        $skill->delete();

        return redirect()->back()->with('success', __('data deleted successfully'));
    }
}
