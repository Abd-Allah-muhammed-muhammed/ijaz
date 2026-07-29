<?php

namespace Modules\Reviews\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Reviews\Http\Resources\Dashboard\ReviewCollection;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewService;

class ReviewController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ReviewService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show reviews', only: ['index']),
            new Middleware('permission:delete reviews', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->paginateForDashboard($request);

        return inertia('Dashboard/Reviews/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ReviewCollection::make($rows),
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->service->delete($review);

        return redirect()->route('dashboard.reviews.index')->with('success', __('data deleted successfully'));
    }
}
