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
| Broadcasting middleware is AuthenticateBroadcasting (see bootstrap/app.php).
| That middleware picks Admin vs Provider when both session cookies exist —
| critical because ProviderLayout subscribes to provider-* / category.* while
| local multi-guard browsers often also have an Admin session.
|
| NEVER type-hint a specific actor model on $user for channels that can be
| hit while a different guard is authenticated — PHP TypeError becomes HTTP
| 500 on /broadcasting/auth. Use instanceof and return false instead.
|
| Channel `guards` options are belt-and-suspenders: Broadcaster::retrieveUser
| will read the named guard even if the default Auth user were wrong.
|
*/

Broadcast::channel('provider-{id}', static function ($user, int $id) {
    return $user instanceof Provider && (int) $user->id === $id;
}, ['guards' => ['provider']]);

Broadcast::channel('user-{id}', static function ($user, $id) {
    return $user instanceof User && (int) $user->id === (int) $id;
}, ['guards' => ['user']]);

Broadcast::channel('admin-{id}', static function ($user, $id) {
    return $user instanceof Admin && (int) $user->id === (int) $id;
}, ['guards' => ['admin']]);

Broadcast::channel('online', static function ($user) {
    // Presence auth must return a plain array — JsonResource objects can serialize incorrectly.
    return ChatUserResource::make($user)->resolve();
});

Broadcast::channel('public', static function ($user) {
    return true;
});

Broadcast::channel('systems.{id}', static function ($user, $id) {
    return $user instanceof Admin;
}, ['guards' => ['admin']]);

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
}, ['guards' => ['provider']]);
