<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\MessageCollection;
use App\Models\Message;
use DB;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

class MessageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show messages', only: ['index', 'show']),
            new Middleware('permission:delete messages', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $rows = Message::query()
            ->when($request->input('search'), function ($query, $v) {
                return $query->where(function (Builder $q) use ($v) {
                    $q->where('name', 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%")
                        ->orWhere('title', 'like', "%{$v}%")
                        ->orWhere('content', 'like', "%{$v}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

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
        DB::beginTransaction();
        try {
            $message->delete();
            DB::commit();

            return to_route('dashboard.messages.index')->with('success', trans('data deleted successfully'));
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);

            return back()->with('error', trans('something went wrong'));
        }

    }
}
