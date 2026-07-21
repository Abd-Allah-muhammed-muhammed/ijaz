<?php

namespace Modules\Cms\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Modules\Cms\Http\Resources\Dashboard\MessageCollection;
use Modules\Cms\Models\Message;
use Modules\Cms\Services\MessageService;
use Throwable;

class MessageController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly MessageService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show messages', only: ['index', 'show']),
            new Middleware('permission:delete messages', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $rows = $this->service->index($request);

        return inertia('Dashboard/Messages/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => MessageCollection::make($rows),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Message $message): RedirectResponse
    {
        try {
            $this->service->destroy($message);

            return to_route('dashboard.messages.index')->with('success', trans('data deleted successfully'));
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', trans('something went wrong'));
        }
    }
}
