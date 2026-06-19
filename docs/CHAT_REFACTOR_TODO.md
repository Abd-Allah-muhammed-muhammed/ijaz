# Chat Module Refactor — Todo List

> Branch: refactor/chat-module
> Last updated: 2026-06-19
> Goal: Move all chat-related code into Modules/Chat/ with clean architecture
>       following Service/Action/DTO/FormRequest/Policy pattern.
>
> Golden Rule: ZERO changes to mobile API contracts.
> Same URLs, same request/response shapes, same broadcast channels.

---

## Phase 1 — Module Scaffold
- [x] Create module skeleton `php artisan module:make Chat`
- [x] Configure `module.json` and `composer.json`
- [x] Create `ChatServiceProvider` + `RouteServiceProvider`
- [x] Register module in `modules_statuses.json`
- [x] Create directory structure:
      ```
      Modules/Chat/
      ├── Actions/
      ├── Contracts/
      ├── DTOs/
      ├── Enums/
      ├── Exceptions/
      ├── Http/
      │   ├── Controllers/
      │   │   ├── V1/
      │   │   ├── Provider/
      │   │   └── Dashboard/
      │   ├── Requests/
      │   └── Resources/
      ├── Infrastructure/
      │   ├── Events/
      │   ├── Jobs/
      │   └── Notifications/
      ├── Handlers/
      ├── Policies/
      ├── Registry/
      ├── Repositories/
      ├── Routes/
      │   ├── V1/
      │   ├── provider.php
      │   └── dashboard.php
      ├── Services/
      └── Support/
      ```
- [x] Create `DTOs/` directory
- [x] Create `Repositories/` directory
- [x] Run `composer dump-autoload`

### Completed: 2026-06-19
### Summary: Created `Modules/Chat/` with Opportunity-style flat structure. Removed `module:make` artifacts (app/, resources/, config/, vite). Added `ChatServiceProvider`, custom `RouteServiceProvider` (API/provider/dashboard/chat routes), empty route stubs, and full directory tree with `.gitkeep`. Registered in `modules_statuses.json`. No business logic, controllers, or migrations.

---

## Phase 2 — Infrastructure (BaseChatService + Events + Jobs + Notifications)
- [ ] Move `BaseChatService` → `Modules/Chat/Infrastructure/BaseChatService.php`
- [ ] Move `MemberChat` → `Modules/Chat/Infrastructure/Features/MemberChat.php`
- [ ] Move `OrderChat` → `Modules/Chat/Infrastructure/Features/OrderChat.php`
- [ ] Move `SupportChat` → `Modules/Chat/Infrastructure/Features/SupportChat.php`
- [ ] Move `NewMessageEvent` → `Modules/Chat/Infrastructure/Events/NewMessageEvent.php`
- [ ] Move `ChatUpdatedEvent` → `Modules/Chat/Infrastructure/Events/ChatUpdatedEvent.php`
- [ ] DELETE `SupportChatUpdatedEvent` (dead/broken — references non-existent App\Models\Chat)
- [ ] Move `NotifyChatMessageReceiver` → `Modules/Chat/Infrastructure/Jobs/NotifyChatMessageReceiver.php`
- [ ] Move `NewMessageSentNotification` → `Modules/Chat/Infrastructure/Notifications/NewMessageSentNotification.php`
- [ ] Move `ChatService` + `IChatService` + `Chat` facade → `Modules/Chat/Services/`
- [ ] Add class aliases in `ChatServiceProvider` for old namespaces (queue safety):
      ```php
      class_alias(
          \Modules\Chat\Infrastructure\Jobs\NotifyChatMessageReceiver::class,
          'App\Services\Chat\Jobs\NotifyChatMessageReceiver'
      );
      ```
- [ ] Move `ChatEventEnum` → `Modules/Chat/Enums/ChatEventEnum.php`
- [ ] Move `HasConversation` contract → `Modules/Chat/Contracts/HasConversation.php`
- [ ] Move `IChatService` → `Modules/Chat/Contracts/IChatService.php`
- [ ] DELETE `Supportable` contract (unused)
- [ ] Update all imports across codebase
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 3 — Shared Messenger
- [ ] Create `ParticipantConversationMessenger` → `Modules/Chat/Support/ParticipantConversationMessenger.php`
      (replaces both OpportunityConversationMessenger and GuarantorConversationMessenger — identical code)
- [ ] Update `Modules/Opportunity/Support/OpportunityConversationMessenger.php`
      to extend `ParticipantConversationMessenger` (or replace entirely)
- [ ] Update `Modules/Guarantor/Support/GuarantorConversationMessenger.php`
      to extend `ParticipantConversationMessenger` (or replace entirely)
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 4 — Chat Type Handlers
- [ ] Create `ChatTypeHandlerInterface` → `Modules/Chat/Contracts/ChatTypeHandlerInterface.php`
      Methods: `operationType()`, `canOpen()`, `participants()`, `listQuery()`, `messenger()`
- [ ] Create `ChatTypeEnum` → `Modules/Chat/Enums/ChatTypeEnum.php`
      Cases: Member, Order, TicketSupport, Opportunity, Guarantor
- [ ] Create `ChatTypeRegistry` → `Modules/Chat/Registry/ChatTypeRegistry.php`
- [ ] Create `MemberChatHandler` → `Modules/Chat/Handlers/MemberChatHandler.php`
- [ ] Create `OrderChatHandler` → `Modules/Chat/Handlers/OrderChatHandler.php`
- [ ] Create `TicketSupportChatHandler` → `Modules/Chat/Handlers/TicketSupportChatHandler.php`
- [ ] Create `OpportunityChatHandler` → `Modules/Chat/Handlers/OpportunityChatHandler.php`
- [ ] Create `GuarantorChatHandler` → `Modules/Chat/Handlers/GuarantorChatHandler.php`
- [ ] Register all handlers in `ChatServiceProvider::boot()`
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 5 — Actions
- [ ] Create `OpenConversationAction` → `Modules/Chat/Actions/OpenConversationAction.php`
- [ ] Create `ListConversationsAction` → `Modules/Chat/Actions/ListConversationsAction.php`
- [ ] Create `ListMessagesAction` → `Modules/Chat/Actions/ListMessagesAction.php`
- [ ] Create `SendMessageAction` → `Modules/Chat/Actions/SendMessageAction.php`
      (marks messages as read, handles attachments, delegates to messenger)
- [ ] Update Opportunity chat actions to use shared actions where possible
- [ ] Update Guarantor chat actions to use shared actions where possible
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 5.5 — DTOs
- [ ] Create `ChatMessageData` → `Modules/Chat/DTOs/ChatMessageData.php`
      Fields: content (nullable string), files (nullable array)
      fromRequest(SendMessageRequest): self
- [ ] Create `StoreConversationData` → `Modules/Chat/DTOs/StoreConversationData.php`  
      Fields: operation_id (string), operation_type (string)
      fromRequest(StoreConversationRequest): self
- [ ] Update all Actions to accept DTOs instead of raw Request objects
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 5.6 — Repositories
- [ ] Create `ConversationRepositoryInterface` → `Modules/Chat/Contracts/Repositories/ConversationRepositoryInterface.php`
      Methods:
      - findOrCreate(Model $operation, Model $user1, Model $user2): Conversation
      - findById(string $id): Conversation
      - listForActor(Model $actor, string $operationType, int $perPage): LengthAwarePaginator
      - listAllForActor(Model $actor, int $perPage): LengthAwarePaginator
- [ ] Create `ConversationRepository` → `Modules/Chat/Repositories/ConversationRepository.php`
      (absorbs Opportunity's ConversationRepository — same logic, generalized)
- [ ] Create `ConversationMessageRepositoryInterface` → `Modules/Chat/Contracts/Repositories/ConversationMessageRepositoryInterface.php`
      Methods:
      - create(Conversation $conversation, Model $sender, Model $receiver, ChatMessageData $data): ConversationMessage
      - listForConversation(Conversation $conversation, int $perPage): LengthAwarePaginator
      - markAsRead(Conversation $conversation, Model $reader): void
- [ ] Create `ConversationMessageRepository` → `Modules/Chat/Repositories/ConversationMessageRepository.php`
- [ ] Bind both interfaces in `ChatServiceProvider::register()`
- [ ] Remove `Modules/Opportunity/Repositories/ConversationRepository.php` (replaced)
- [ ] Remove `Modules/Opportunity/Contracts/Repositories/ConversationRepositoryInterface.php` (replaced)
- [ ] Update Opportunity chat actions to use `Modules/Chat/Contracts/Repositories/ConversationRepositoryInterface.php`
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 6 — Resources & Requests
- [ ] Move `ConversationResource` → `Modules/Chat/Http/Resources/ConversationResource.php`
- [ ] Move `ConversationCollection` → `Modules/Chat/Http/Resources/ConversationCollection.php`
- [ ] Move `ConversationMessageResource` → `Modules/Chat/Http/Resources/ConversationMessageResource.php`
- [ ] Move `ConversationMessageCollection` → `Modules/Chat/Http/Resources/ConversationMessageCollection.php`
- [ ] Move `ConversationAttachmentResource` → `Modules/Chat/Http/Resources/ConversationAttachmentResource.php`
- [ ] Move `SendMessageRequest` → `Modules/Chat/Http/Requests/SendMessageRequest.php`
      (resolve name collision with Guarantor's SendMessageRequest)
- [ ] Move `StoreConversationRequest` → `Modules/Chat/Http/Requests/StoreConversationRequest.php`
- [ ] Move `SendSupportMessageRequest` → `Modules/Chat/Http/Requests/SendSupportMessageRequest.php`
- [ ] Update all imports
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 7 — Unified ConversationPolicy
- [ ] Create unified `ConversationPolicy` → `Modules/Chat/Policies/ConversationPolicy.php`
      Type-aware: delegates to handler for authorization
- [ ] Remove `ConversationPolicy` from `Modules/Opportunity/Policies/`
- [ ] Remove `ConversationPolicy` from `Modules/Guarantor/Policies/`
- [ ] Update `OpportunityServiceProvider` — remove Gate::policy for Conversation
- [ ] Update `GuarantorServiceProvider` — remove Gate::policy for Conversation
- [ ] Register unified policy in `ChatServiceProvider::boot()`
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 8 — API Controllers
- [ ] Create `Modules/Chat/Http/Controllers/V1/ChatController.php`
      (replaces App\Http\Controllers\Api\V1\ChatController — member chat)
- [ ] Create `Modules/Chat/Http/Controllers/V1/OrderChatController.php`
      (replaces App\Http\Controllers\Api\V1\OrderChatController)
- [ ] Create `Modules/Chat/Http/Controllers/V1/TicketSupportChatController.php`
      (replaces App\Http\Controllers\Api\V1\TicketSupportChatController)
- [ ] Move `OpportunityChatController` → `Modules/Chat/Http/Controllers/V1/OpportunityChatController.php`
      OR keep in Opportunity module (decide in phase)
- [ ] Move `GuarantorChatController` → `Modules/Chat/Http/Controllers/V1/GuarantorChatController.php`
      OR keep in Guarantor module (decide in phase)
- [ ] All controllers: thin pattern — validate → authorize → action → response
- [ ] Remove dead methods: `sendToProvider`, `sendToUser`, `sendToSupport`, `supportMessages`
- [ ] Fix `ChatController::index()` wrong guard (`auth('provider')` on sanctum route)
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 9 — Provider Dashboard Controllers
- [ ] Move `App\Http\Controllers\Provider\ChatController`
      → `Modules/Chat/Http/Controllers/Provider/ChatController.php`
- [ ] Move `App\Http\Controllers\Provider\OrderChatController`
      → `Modules/Chat/Http/Controllers/Provider/OrderChatController.php`
- [ ] Update provider routes
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 10 — Admin Dashboard Controllers
- [ ] Extract chat methods from `App\Http\Controllers\Dashboard\SupportController`
      → `Modules/Chat/Http/Controllers/Dashboard/SupportChatController.php`
- [ ] Update dashboard routes
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 11 — Routes
- [ ] Create `Modules/Chat/Routes/V1/chat.php`
      (all api/v1/chats/* routes in one place)
- [ ] Create `Modules/Chat/Routes/provider.php`
- [ ] Create `Modules/Chat/Routes/dashboard.php`
- [ ] Register all routes in `ChatServiceProvider`
- [ ] Remove old chat routes from `routes/Api/V1/catalog.php`
- [ ] Remove old chat routes from `routes/dashboard.php`
- [ ] Remove old chat routes from `routes/provider.php`
- [ ] Verify `php artisan route:list` shows all routes intact
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 12 — Cleanup
- [ ] DELETE `app/Services/Chat/` (entire directory — all moved)
- [ ] DELETE `app/Http/Controllers/Api/V1/ChatController.php`
- [ ] DELETE `app/Http/Controllers/Api/V1/OrderChatController.php`
- [ ] DELETE `app/Http/Controllers/Api/V1/TicketSupportChatController.php`
- [ ] DELETE `app/Http/Controllers/Provider/ChatController.php`
- [ ] DELETE `app/Http/Controllers/Provider/OrderChatController.php`
- [ ] DELETE `app/Http/Resources/Dashboard/Chat/` (moved to module)
- [ ] Remove `IChatService` binding from `AppServiceProvider`
      (now in `ChatServiceProvider`)
- [ ] Remove `NewNotificationSendEvent` and `ProviderLocationChangedEvent`
      from `app/Services/Chat/Events/` — not chat concerns, move to appropriate location
- [ ] Run full test suite — all must pass
- [ ] Verify `php artisan route:list` — same routes as before
- [ ] `npm run build` — no errors

### Completed: —
### Summary: —

---

## Phase 13 — Fix Bugs & Typos
- [ ] `lastMassage` → `lastMessage` in Conversation model and ALL references
- [ ] `last_massage_at` → `last_message_at` in resources and events
- [ ] `ChatUerException` → `ChatUserException` (rename file + class)
- [ ] `reseriverDoesNotExist` → `receiverDoesNotExist` in exceptions
- [ ] `replay` → `reply` in MemberChat and SupportChat
- [ ] Fix `ConversationAttachment::chat()` wrong FK
      (`belongsTo(Conversation::class)` but FK is `conversation_message_id`)
- [ ] Fix `ConversationMessage` missing `SoftDeletes` trait
      (migration has `softDeletes()` but model lacks trait)
- [ ] Fix `NotifyChatMessageReceiver` type hint `User` → `HasConversation`
- [ ] Fix `OrderChat::getReceiver()` hardcoded user1/user2 assumption
- [ ] Fix `UserController` unread count using `sender` instead of `receiver`
- [ ] Delete `SupportChatUpdatedEvent` (references non-existent App\Models\Chat)
- [ ] Remove `Supportable` contract (unused)
- [ ] Run tests — all must pass

### Completed: —
### Summary: —

---

## Phase 14 — Tests
- [ ] Tests for `ParticipantConversationMessenger`
- [ ] Tests for `ChatTypeRegistry` + all handlers
- [ ] Tests for `OpenConversationAction`
- [ ] Tests for `ListConversationsAction`
- [ ] Tests for `ListMessagesAction`
- [ ] Tests for `SendMessageAction`
- [ ] Tests for unified `ConversationPolicy`
- [ ] Tests for all API controllers (member, order, ticket, opportunity, guarantor)
- [ ] Tests for Provider dashboard controllers
- [ ] Tests for broadcasting (Event::fake)
- [ ] Tests for `NotifyChatMessageReceiver` job
- [ ] Full regression — all existing Opportunity + Guarantor tests pass

### Completed: —
### Summary: —

---

## Completed Steps Log
<!-- Each completed phase gets moved here with summary -->

### Phase 1 — Module Scaffold (2026-06-19)
Created `Modules/Chat/` skeleton: providers, empty route files, full directory tree. Module enabled in `modules_statuses.json`. Verified via `php artisan module:list`.

---

## Notes
- Never change broadcast channel names: `chats.{id}`, private participant channels
- Never change broadcastAs() values: `new-message`, `chat-updated`
- Never change DB table names or morph type values
- Never change API URLs or response shapes
- Class aliases maintained in ChatServiceProvider until queue is drained
