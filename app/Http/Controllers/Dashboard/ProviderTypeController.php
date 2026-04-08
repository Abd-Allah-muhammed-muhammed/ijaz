<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderTypeRequest;
use App\Http\Resources\Dashboard\CategoryCollection;
use App\Http\Resources\Dashboard\ProviderTypeCollection;
use App\Http\Resources\Dashboard\ProviderTypeResource;
use App\Models\Category;
use App\Models\ProviderType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProviderTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show providerTypes', only: ['index', 'show']),
            new Middleware('permission:create providerTypes', only: ['create', 'store']),
            new Middleware('permission:edit providerTypes', only: ['edit', 'update']),
            new Middleware('permission:delete providerTypes', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $row = ProviderType::with(['translation'])
            ->withCount('providers')
            ->when($request->input('search'), function ($query, $v) {
                return $query->whereTranslationLike('name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/ProviderTypes/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ProviderTypeCollection::make($row),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProviderType $providerType)
    {
        $providerType->load('translations', 'categories.translations');

        return inertia('Dashboard/ProviderTypes/Edit', [
            'row' => ProviderTypeResource::make($providerType),
            'categories' => CategoryCollection::make(Category::whereNull('parent_id')->with('translations')->get()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(ProviderTypeRequest $request, ProviderType $providerType): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('provider-types', 'public');
                $providerType->deleteImage();
            } else {
                unset($data['image']);
            }
            $providerType->update($data);
            if (isset($data['categories'])) {
                $providerType->categories()->sync($data['categories']);
            } else {
                $providerType->categories()->detach();
            }

            DB::commit();

            return redirect()->route('dashboard.provider-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(ProviderTypeRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $data['image'] = $request->file('image')->store('provider-types', 'public');
            $providerType = ProviderType::create($data);
            if (isset($data['categories'])) {
                $providerType->categories()->sync($data['categories']);
            }
            DB::commit();

            return redirect()->route('dashboard.provider-types.index')->with('success', __('data saved successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);
            throw $th;

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/ProviderTypes/Create', [
            'categories' => CategoryCollection::make(Category::whereNull('parent_id')->with('translations')->get()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(ProviderType $providerType)
    {
        DB::beginTransaction();
        try {
            if ($providerType->providers()->exists()) {
                DB::rollBack();

                return redirect()->back()->with('error', __('Sorry, unable to execute this action due to existing data'));
            }
            $providerType->delete();
            DB::commit();

            return redirect()->route('dashboard.provider-types.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
