<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\NationalityRequest;
use App\Http\Resources\Dashboard\NationalityCollection;
use App\Http\Resources\Dashboard\NationalityResource;
use App\Models\Nationality;
use App\Services\Normalize\Normalize;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class NationalityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show nationalities', only: ['index', 'show']),
            new Middleware('permission:create nationalities', only: ['create', 'store']),
            new Middleware('permission:edit nationalities', only: ['edit', 'update']),
            new Middleware('permission:delete nationalities', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $row = Nationality::query()
            ->with(['translation'])
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Nationalities/Index', [
            'params' => $request->all() ?: [],
            'rows' => NationalityCollection::make($row),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(NationalityRequest $request)
    {
        DB::beginTransaction();
        try {
            Nationality::create($request->validated());
            DB::commit();

            return redirect()->route('dashboard.nationalities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Dashboard/Nationalities/Create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Nationality $nationality)
    {
        return inertia('Dashboard/Nationalities/Edit', [
            'row' => NationalityResource::make($nationality),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(NationalityRequest $request, Nationality $nationality)
    {
        DB::beginTransaction();
        try {
            $nationality->update($request->validated());
            DB::commit();

            return redirect()->route('dashboard.nationalities.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Nationality $nationality)
    {
        if ($nationality->users()->exists()) {
            return redirect()->back()->with('error', __('dashboard.nationalities.delete_error'));
        }
        $nationality->delete();

        return redirect()->route('dashboard.nationalities.index')->with('success', __('data deleted successfully'));
    }
}
