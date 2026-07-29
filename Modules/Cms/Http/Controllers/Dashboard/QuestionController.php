<?php

namespace Modules\Cms\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Cms\DTOs\StoreQuestionDTO;
use Modules\Cms\DTOs\UpdateQuestionDTO;
use Modules\Cms\Http\Requests\Dashboard\QuestionRequest;
use Modules\Cms\Http\Resources\Dashboard\QuestionCollection;
use Modules\Cms\Http\Resources\Dashboard\QuestionResource;
use Modules\Cms\Models\Question;
use Modules\Cms\Services\QuestionService;
use Throwable;

class QuestionController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly QuestionService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show questions', only: ['index']),
            new Middleware('permission:create questions', only: ['create', 'store']),
            new Middleware('permission:edit questions', only: ['edit', 'update']),
            new Middleware('permission:delete questions', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        return inertia('Dashboard/Questions/Index', [
            'rows' => function () use ($request) {
                $rows = $this->service->index($request);

                return QuestionCollection::make($rows);
            },
            'prams' => fn () => $request->all() ?: [],
        ]);
    }

    public function create(): Response
    {
        return inertia('Dashboard/Questions/Create');
    }

    public function edit(Question $question): Response
    {
        $question = $this->service->show($question);

        return inertia('Dashboard/Questions/Edit', [
            'row' => QuestionResource::make($question),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(QuestionRequest $request): RedirectResponse
    {
        try {
            $this->service->store(StoreQuestionDTO::fromValidated($request->validated()));

            return to_route('dashboard.questions.index')->with('success', __('data saved successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        try {
            $this->service->update($question, UpdateQuestionDTO::fromValidated($request->validated()));

            return to_route('dashboard.questions.index')->with('success', __('data updated successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(Question $question): RedirectResponse
    {
        try {
            $this->service->destroy($question);

            return to_route('dashboard.questions.index')->with('success', __('data deleted successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', $throwable->getMessage());
        }
    }
}
