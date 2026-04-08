<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\QuestionRequest;
use App\Http\Resources\Dashboard\QuestionCollection;
use App\Http\Resources\Dashboard\QuestionResource;
use App\Models\Question;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class QuestionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show questions', only: ['index', 'show']),
            new Middleware('permission:create questions', only: ['create', 'store']),
            new Middleware('permission:edit questions', only: ['edit', 'update']),
            new Middleware('permission:delete questions', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return inertia('Dashboard/Questions/Index', [
            'rows' => function () use ($request) {
                $query = Question::query()
                    ->with('translations')
                    ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
                    ->latest()
                    ->paginate(10)->withQueryString();

                return QuestionCollection::make($query);
            },
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(QuestionRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            Question::create($request->validated());
            DB::commit();

            return to_route('dashboard.questions.index')->with('success', __('data saved successfully'));
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
        return inertia('Dashboard/Questions/Create');
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        return inertia('Dashboard/Questions/Edit', [
            'row' => QuestionResource::make($question->load('translations')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $question->update($request->validated());
            DB::commit();

            return to_route('dashboard.questions.index')->with('success', __('data updated successfully'));
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
    public function destroy(Question $question): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $question->delete();
            DB::commit();

            return to_route('dashboard.questions.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }
}
