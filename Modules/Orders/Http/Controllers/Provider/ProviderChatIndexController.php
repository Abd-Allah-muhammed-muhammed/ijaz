<?php

namespace Modules\Orders\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Chat\Http\Resources\Dashboard\ConversationCollection;
use Modules\Orders\Services\OrderService;

class ProviderChatIndexController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var Provider $provider */
        $provider = auth('provider')->user();

        $rows = $this->orderService->listConversationsForProvider(
            $provider,
            $request->integer('per_page', 10),
        );

        return inertia('Provider/Chat/Index', [
            'prams' => $request->all() ?: [],
            'rows' => ConversationCollection::make($rows),
            'current_conversation' => $request->filled('conversation')
                ? $rows->firstWhere('id', $request->get('conversation'))
                : null,
        ]);
    }
}
