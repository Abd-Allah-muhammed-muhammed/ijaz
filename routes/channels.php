<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Chat\Http\Resources\ChatUserResource;
use Modules\Chat\Models\Conversation;
use Modules\Marketplace\Models\Category;

/*
|--------------------------------------------------------------------------
| Channel authorization
|--------------------------------------------------------------------------
|
| Broadcasting middleware is `auth:admin,provider` (see bootstrap/app.php).
| When an Admin is authenticated, Echo may still attempt to authorize
| Provider-only channels (e.g. leftover provider subscriptions in local
| multi-guard browser sessions, or reconnect after WebSocket comes up).
|
| NEVER type-hint a specific actor model on $user for channels that can be
| hit while a different guard is authenticated — PHP TypeError becomes HTTP
| 500 on /broadcasting/auth. Use instanceof and return false instead.
|
*/

Broadcast::channel('provider-{id}', static function ($user, int $id) {
    return $user instanceof Provider && (int) $user->id === $id;
});

Broadcast::channel('user-{id}', static function ($user, $id) {
    return $user instanceof User && (int) $user->id === (int) $id;
});

Broadcast::channel('admin-{id}', static function ($user, $id) {
    return $user instanceof Admin && (int) $user->id === (int) $id;
});

Broadcast::channel('online', static function ($user) {
    // Presence auth must return a plain array — JsonResource objects can serialize incorrectly.
    return ChatUserResource::make($user)->resolve();
});

Broadcast::channel('public', static function ($user) {
    return true;
});

Broadcast::channel('systems.{id}', static function ($user, $id) {
    return $user instanceof Admin;
});

Broadcast::channel('chats.{chat}', static function ($user, Conversation $chat) {
    // Admins may join any conversation for support oversight (orders, tickets, etc.).
    if ($user instanceof Admin) {
        return ChatUserResource::make($user)->resolve();
    }
    if ($chat->user1()->is($user) || $chat->user2()->is($user)) {
        return ChatUserResource::make($user)->resolve();
    }

    return false;
});

Broadcast::channel('category.{category}', static function ($user, Category $category) {
    if (! $user instanceof Provider) {
        return false;
    }

    return $user->categories()->where('categories.id', $category->id)->exists();
});
