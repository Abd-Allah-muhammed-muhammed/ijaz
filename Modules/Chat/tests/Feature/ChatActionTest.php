<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Actions\ListConversationsAction;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\OpenConversationAction;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Handlers\MemberChatHandler;
use Modules\Chat\Handlers\OrderChatHandler;
use Modules\Chat\Http\Requests\SendMessageRequest;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Repositories\ConversationMessageRepository;
use Modules\Chat\Repositories\ConversationRepository;

test('OpenConversationAction creates conversation for order', function () {
    ['user' => $user, 'order' => $order] = createOrderWithParticipants();
    $action = app(OpenConversationAction::class);

    $conversation = $action->handle($user, $order, new OrderChatHandler);

    expect($conversation->operation_type)->toBe(Order::class)
        ->and($conversation->operation_id)->toBe($order->getKey());
});

test('OpenConversationAction returns existing conversation if already exists (idempotent)', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $existing = createOrderConversation($user, $provider, $order);
    $action = app(OpenConversationAction::class);

    $conversation = $action->handle($user, $order, new OrderChatHandler);

    expect($conversation->is($existing))->toBeTrue()
        ->and(Conversation::query()->where('operation_type', Order::class)->where('operation_id', $order->getKey())->count())->toBe(1);
});

test('OpenConversationAction handleMemberChat creates P2P conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $action = app(OpenConversationAction::class);

    $conversation = $action->handleMemberChat($user1, $user2);

    expect($conversation->operation_type)->toBeNull()
        ->and($conversation->user1_id)->toBe($user1->getKey())
        ->and($conversation->user2_id)->toBe($user2->getKey());
});

test('OpenConversationAction handleMemberChat is idempotent', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $action = app(OpenConversationAction::class);

    $first = $action->handleMemberChat($user1, $user2);
    $second = $action->handleMemberChat($user1, $user2);

    expect($first->is($second))->toBeTrue()
        ->and(Conversation::query()->whereNull('operation_type')->count())->toBe(1);
});

test('ListConversationsAction returns paginated conversations for actor', function () {
    $user = User::factory()->create();
    createMemberConversation($user, User::factory()->create());
    createMemberConversation($user, User::factory()->create());

    $paginator = app(ListConversationsAction::class)->handle($user, new MemberChatHandler, 15);

    expect($paginator->total())->toBe(2);
});

test('ListConversationsAction only returns conversations for correct type', function () {
    $user = User::factory()->create();
    ['provider' => $provider, 'order' => $order] = createOrderWithParticipants($user);

    createMemberConversation($user, User::factory()->create());
    createOrderConversation($user, $provider, $order);

    $memberResults = app(ListConversationsAction::class)->handle($user, new MemberChatHandler)->total();
    $orderResults = app(ListConversationsAction::class)->handle($user, new OrderChatHandler)->total();

    expect($memberResults)->toBe(1)
        ->and($orderResults)->toBe(1);
});

test('ListMessagesAction returns paginated messages', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user1->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'content' => 'Hello',
    ]);

    $paginator = app(ListMessagesAction::class)->handle($conversation, $user1);

    expect($paginator->total())->toBe(1);
});

test('ListMessagesAction marks messages as read for actor', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user1->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'content' => 'Hello',
    ]);

    app(ListMessagesAction::class)->handle($conversation, $user2);

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('ListMessagesAction does not mark own sent messages as read', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user1->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'content' => 'Hello',
    ]);

    app(ListMessagesAction::class)->handle($conversation, $user1);

    expect($message->fresh()->read_at)->toBeNull();
});

test('SendMessageAction sends text message', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->load(['user1', 'user2']);

    $message = app(SendMessageAction::class)->handle(
        $conversation,
        $user1,
        new ChatMessageData(content: 'Test message'),
        new MemberChatHandler,
    );

    expect($message->content)->toBe('Test message');
});

test('SendMessageAction sends file message', function () {
    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);
    Storage::fake('public');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);
    $conversation->load(['user1', 'user2']);

    $message = app(SendMessageAction::class)->handle(
        $conversation,
        $user1,
        new ChatMessageData(files: [UploadedFile::fake()->image('photo.jpg')]),
        new MemberChatHandler,
    );

    expect($message->has_attachments)->toBeTruthy();
});

test('SendMessageAction requires content or files', function () {
    $request = SendMessageRequest::create('/test', 'POST', []);
    $validator = validator($request->all(), (new SendMessageRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('content'))->toBeTrue()
        ->and($validator->errors()->has('files'))->toBeTrue();
});

test('ConversationRepository findOrCreate creates new conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $repo = app(ConversationRepository::class);

    $conversation = $repo->findOrCreate(null, $user1, $user2);

    expect($conversation->exists)->toBeTrue()
        ->and($conversation->operation_type)->toBeNull();
});

test('ConversationRepository findOrCreate returns existing conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $existing = createMemberConversation($user1, $user2);
    $repo = app(ConversationRepository::class);

    $conversation = $repo->findOrCreate(null, $user1, $user2);

    expect($conversation->is($existing))->toBeTrue();
});

test('ConversationRepository findById loads relations', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());
    $repo = app(ConversationRepository::class);

    $found = $repo->findById($conversation->id);

    expect($found->relationLoaded('user1'))->toBeTrue()
        ->and($found->relationLoaded('user2'))->toBeTrue()
        ->and($found->relationLoaded('lastMessage'))->toBeTrue();
});

test('ConversationRepository listForActor filters by operation type', function () {
    $user = User::factory()->create();
    ['provider' => $provider, 'order' => $order] = createOrderWithParticipants($user);

    createMemberConversation($user, User::factory()->create());
    createOrderConversation($user, $provider, $order);
    $repo = app(ConversationRepository::class);

    $memberCount = $repo->listForActor($user, null)->total();
    $orderCount = $repo->listForActor($user, Order::class)->total();

    expect($memberCount)->toBe(1)
        ->and($orderCount)->toBe(1);
});

test('ConversationMessageRepository listForConversation paginates messages', function () {
    $conversation = createMemberConversation(User::factory()->create(), User::factory()->create());
    $user = User::factory()->create();

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => User::factory()->create()->getKey(),
        'content' => 'Hi',
    ]);

    $paginator = app(ConversationMessageRepository::class)->listForConversation($conversation);

    expect($paginator->total())->toBe(1);
});

test('ConversationMessageRepository markAsRead updates read_at', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user1->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'content' => 'Hello',
    ]);

    app(ConversationMessageRepository::class)->markAsRead($conversation, $user2);

    expect($message->fresh()->read_at)->not->toBeNull();
});

test('ConversationMessageRepository does not mark sender messages as read', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = createMemberConversation($user1, $user2);

    $message = ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user1->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user2->getKey(),
        'content' => 'Hello',
    ]);

    app(ConversationMessageRepository::class)->markAsRead($conversation, $user1);

    expect($message->fresh()->read_at)->toBeNull();
});
