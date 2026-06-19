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
- [x] Move `BaseChatService` → `Modules/Chat/Infrastructure/BaseChatService.php`
- [x] Move `MemberChat` → `Modules/Chat/Infrastructure/Features/MemberChat.php`
- [x] Move `OrderChat` → `Modules/Chat/Infrastructure/Features/OrderChat.php`
- [x] Move `SupportChat` → `Modules/Chat/Infrastructure/Features/SupportChat.php`
- [x] Move `NewMessageEvent` → `Modules/Chat/Infrastructure/Events/NewMessageEvent.php`
- [x] Move `ChatUpdatedEvent` → `Modules/Chat/Infrastructure/Events/ChatUpdatedEvent.php`
- [x] DELETE `SupportChatUpdatedEvent` (dead/broken — references non-existent App\Models\Chat)
- [x] Move `NotifyChatMessageReceiver` → `Modules/Chat/Infrastructure/Jobs/NotifyChatMessageReceiver.php`
- [x] Move `NewMessageSentNotification` → `Modules/Chat/Infrastructure/Notifications/NewMessageSentNotification.php`
- [x] Move `ChatService` + `IChatService` + `Chat` facade → `Modules/Chat/Services/`
- [x] Add class aliases in `ChatServiceProvider` for old namespaces (queue safety):
      ```php
      class_alias(
          \Modules\Chat\Infrastructure\Jobs\NotifyChatMessageReceiver::class,
          'App\Services\Chat\Jobs\NotifyChatMessageReceiver'
      );
      ```
- [x] Move `ChatEventEnum` → `Modules/Chat/Enums/ChatEventEnum.php`
- [x] Move `HasConversation` contract → `Modules/Chat/Contracts/HasConversation.php`
- [x] Move `IChatService` → `Modules/Chat/Contracts/IChatService.php`
- [x] DELETE `Supportable` contract (unused)
- [x] Update all imports across codebase
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Moved core chat infrastructure from `app/Services/Chat/` into `Modules/Chat/` (BaseChatService, feature classes, events, jobs, notifications, ChatService, contracts, enum, facade). Left `@deprecated` backward-compat aliases in old `app/` paths. Bound `IChatService` in `ChatServiceProvider` with queue-safe `class_alias` for `NotifyChatMessageReceiver`. Removed binding from `AppServiceProvider`. Updated Opportunity/Guarantor messengers to import from module namespace. Deleted dead `SupportChatUpdatedEvent` and unused `Supportable`. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 3 — Shared Messenger
- [x] Create `ParticipantConversationMessenger` → `Modules/Chat/Support/ParticipantConversationMessenger.php`
      (replaces both OpportunityConversationMessenger and GuarantorConversationMessenger — identical code)
- [x] Update `Modules/Opportunity/Support/OpportunityConversationMessenger.php`
      to extend `ParticipantConversationMessenger` (or replace entirely)
- [x] Update `Modules/Guarantor/Support/GuarantorConversationMessenger.php`
      to extend `ParticipantConversationMessenger` (or replace entirely)
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Extracted identical messenger logic into `Modules/Chat/Support/ParticipantConversationMessenger` (sendAs, getOnlineUsers, getReceiver with UUID string-cast comparison). Opportunity and Guarantor messengers are now thin wrappers extending the shared class. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 4 — Chat Type Handlers
- [x] Create `ChatTypeHandlerInterface` → `Modules/Chat/Contracts/ChatTypeHandlerInterface.php`
      Methods: `operationType()`, `canOpen()`, `participants()`, `listQuery()`, `messenger()`
- [x] Create `ChatTypeEnum` → `Modules/Chat/Enums/ChatTypeEnum.php`
      Cases: Member, Order, TicketSupport, Opportunity, Guarantor
- [x] Create `ChatTypeRegistry` → `Modules/Chat/Registry/ChatTypeRegistry.php`
- [x] Create `MemberChatHandler` → `Modules/Chat/Handlers/MemberChatHandler.php`
- [x] Create `OrderChatHandler` → `Modules/Chat/Handlers/OrderChatHandler.php`
- [x] Create `TicketSupportChatHandler` → `Modules/Chat/Handlers/TicketSupportChatHandler.php`
- [x] Create `OpportunityChatHandler` → `Modules/Chat/Handlers/OpportunityChatHandler.php`
- [x] Create `GuarantorChatHandler` → `Modules/Chat/Handlers/GuarantorChatHandler.php`
- [x] Register all handlers in `ChatServiceProvider::boot()`
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Added `ChatTypeHandlerInterface`, `ChatTypeEnum`, singleton `ChatTypeRegistry`, and five thin handlers (Member, Order, TicketSupport, Opportunity, Guarantor) registered in `ChatServiceProvider::boot()`. Handlers define operation_type, list queries, participants, and delegate messaging to `ParticipantConversationMessenger`. Opportunity handler uses `author`/`acceptedOffer.author` to match domain model. No controller/route changes yet. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 5 — Actions
- [x] Create `OpenConversationAction` → `Modules/Chat/Actions/OpenConversationAction.php`
- [x] Create `ListConversationsAction` → `Modules/Chat/Actions/ListConversationsAction.php`
- [x] Create `ListMessagesAction` → `Modules/Chat/Actions/ListMessagesAction.php`
- [x] Create `SendMessageAction` → `Modules/Chat/Actions/SendMessageAction.php`
      (marks messages as read, handles attachments, delegates to messenger)
- [x] Update Opportunity chat actions to use shared actions where possible
- [x] Update Guarantor chat actions to use shared actions where possible
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Created four shared Chat actions (open, list conversations, list messages, send message). Opportunity and Guarantor module actions now delegate to shared actions via `ChatTypeRegistry`. Added `handleMemberChat()` for direct P2P conversations. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 5.5 — DTOs
- [x] Create `ChatMessageData` → `Modules/Chat/DTOs/ChatMessageData.php`
      Fields: content (nullable string), files (nullable array)
      fromRequest(SendMessageRequest): self
- [x] Create `StoreConversationData` → `Modules/Chat/DTOs/StoreConversationData.php`  
      Fields: operation_id (string), operation_type (string)
      fromRequest(StoreConversationRequest): self
- [x] Update all Actions to accept DTOs instead of raw Request objects
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Added `ChatMessageData` and `StoreConversationData` readonly DTOs with `fromRequest()` factories. Module send actions convert requests to DTOs at the service layer.

---

## Phase 5.6 — Repositories
- [x] Create `ConversationRepositoryInterface` → `Modules/Chat/Contracts/Repositories/ConversationRepositoryInterface.php`
      Methods:
      - findOrCreate(Model $operation, Model $user1, Model $user2): Conversation
      - findById(string $id): Conversation
      - listForActor(Model $actor, string $operationType, int $perPage): LengthAwarePaginator
      - listAllForActor(Model $actor, int $perPage): LengthAwarePaginator
- [x] Create `ConversationRepository` → `Modules/Chat/Repositories/ConversationRepository.php`
      (absorbs Opportunity's ConversationRepository — same logic, generalized)
- [x] Create `ConversationMessageRepositoryInterface` → `Modules/Chat/Contracts/Repositories/ConversationMessageRepositoryInterface.php`
      Methods:
      - create(Conversation $conversation, Model $sender, Model $receiver, ChatMessageData $data): ConversationMessage
      - listForConversation(Conversation $conversation, int $perPage): LengthAwarePaginator
      - markAsRead(Conversation $conversation, Model $reader): void
- [x] Create `ConversationMessageRepository` → `Modules/Chat/Repositories/ConversationMessageRepository.php`
- [x] Bind both interfaces in `ChatServiceProvider::register()`
- [x] Remove `Modules/Opportunity/Repositories/ConversationRepository.php` (replaced)
- [x] Remove `Modules/Opportunity/Contracts/Repositories/ConversationRepositoryInterface.php` (replaced)
- [x] Update Opportunity chat actions to use `Modules/Chat/Contracts/Repositories/ConversationRepositoryInterface.php`
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Added `ConversationRepository` and `ConversationMessageRepository` with interface bindings in `ChatServiceProvider`. Removed Opportunity-specific conversation repository. `ListMessagesAction` uses message repository for mark-as-read and pagination. Added status filters to Opportunity/Guarantor handler `listQuery()` to preserve existing list behavior.

---

## Phase 6 — Resources & Requests
- [x] Move `ConversationResource` → `Modules/Chat/Http/Resources/ConversationResource.php`
- [x] Move `ConversationCollection` → `Modules/Chat/Http/Resources/ConversationCollection.php`
- [x] Move `ConversationMessageResource` → `Modules/Chat/Http/Resources/ConversationMessageResource.php`
- [x] Move `ConversationMessageCollection` → `Modules/Chat/Http/Resources/ConversationMessageCollection.php`
- [x] Move `ConversationAttachmentResource` → `Modules/Chat/Http/Resources/ConversationAttachmentResource.php`
- [x] Move `SendMessageRequest` → `Modules/Chat/Http/Requests/SendMessageRequest.php`
      (resolve name collision with Guarantor's SendMessageRequest)
- [x] Move `StoreConversationRequest` → `Modules/Chat/Http/Requests/StoreConversationRequest.php`
- [x] Move `SendSupportMessageRequest` → `Modules/Chat/Http/Requests/SendSupportMessageRequest.php`
- [x] Update all imports
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Moved base chat resources, requests, and dashboard collections to `Modules/Chat/Http/`. Renamed `UserResource` → `ChatUserResource`. Left `@deprecated` aliases in all old `app/` paths. Renamed Guarantor request to `SendGuarantorMessageRequest` (alias kept). Opportunity/Guarantor conversation resources stay in modules — different response shapes from base. Updated all controller imports. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 7 — Unified ConversationPolicy
- [x] Create unified `ConversationPolicy` → `Modules/Chat/Policies/ConversationPolicy.php`
      Type-aware: delegates to handler for authorization
- [x] Remove `ConversationPolicy` from `Modules/Opportunity/Policies/`
- [x] Remove `ConversationPolicy` from `Modules/Guarantor/Policies/`
- [x] Update `OpportunityServiceProvider` — remove Gate::policy for Conversation
- [x] Update `GuarantorServiceProvider` — remove Gate::policy for Conversation
- [x] Register unified policy in `ChatServiceProvider::boot()`
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Created unified `Modules/Chat\Policies\ConversationPolicy` (participant-only: view, send, open with UUID string-cast). Registered via `$this->app->booted()` in `ChatServiceProvider` so it wins over module providers. Removed duplicate Gate registrations from Opportunity and Guarantor. Module policies replaced with deprecated aliases. Fixes production bug where last module registration broke the other chat type. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 8 — API Controllers
- [x] Create `Modules/Chat/Http/Controllers/V1/MemberChatController.php`
      (replaces App\Http\Controllers\Api\V1\ChatController — member chat methods only)
- [x] Create `Modules/Chat/Http/Controllers/V1/OrderChatController.php`
      (replaces App\Http\Controllers\Api\V1\OrderChatController)
- [x] Create `Modules/Chat/Http/Controllers/V1/TicketSupportChatController.php`
      (replaces App\Http\Controllers\Api\V1\TicketSupportChatController)
- [x] **Decision:** Keep `OpportunityChatController` in `Modules/Opportunity/` and `GuarantorChatController` in `Modules/Guarantor/` — domain-specific policies, resources, and services; moving adds no value
- [x] All controllers: thin pattern — validate → authorize → action → response
- [x] Dead methods excluded from new controllers: `sendToProvider`, `sendToUser`, `sendToSupport`, `supportMessages`
- [x] `MemberChatController::index()` uses `auth()->user()` (fixes old `auth('provider')` bug on sanctum route)
- [x] Create `StoreOrderChatRequest` for order chat store validation
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Created thin V1 controllers (Member, Order, TicketSupport) delegating to Chat actions via `ChatTypeRegistry`. Member store preserves `socket_id` receiver resolution. Old `app/` controllers untouched; routes still point to them (Phase 11). Opportunity and Guarantor controllers remain in their modules. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 9 — Provider Dashboard Controllers
- [x] Create `Modules/Chat/Http/Controllers/Provider/MemberChatController.php`
      (replaces Provider ChatController chat API methods)
- [x] Create `Modules/Chat/Http/Controllers/Provider/OrderChatController.php`
      (replaces Provider OrderChatController)
- [x] Provider controllers use `auth('provider')->user()` throughout
- [x] Old `app/` Provider controllers untouched — routes unchanged until Phase 11
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Created thin Provider `MemberChatController` and `OrderChatController` delegating to Chat actions via `ChatTypeRegistry`. Fixes old bugs (`auth()->user()` on provider routes, ChatController listing orders instead of member chats). Old Inertia-based Provider controllers remain until Phase 11.

---

## Phase 10 — Admin Dashboard Controllers
- [x] Extract chat methods → `Modules/Chat/Http/Controllers/Dashboard/SupportChatController.php`
      (`show`, `send` for ticket support chat)
- [x] Non-chat methods remain in `app/Http/Controllers/Dashboard/SupportController.php`
      (`index`, `show`, `updateStatus`, `openChat`)
- [x] Dashboard controller uses `auth('admin')->user()`
- [x] Old SupportController untouched — routes unchanged until Phase 11
- [x] Run tests — all must pass

### Completed: 2026-06-19
### Summary: Created `SupportChatController` with `show` and `send` for admin ticket chat. `openChat` welcome message and ticket CRUD stay in `SupportController`. Opportunity (81) and Guarantor (147) tests pass.

---

## Phase 11 — Routes
- [x] Create `Modules/Chat/Routes/V1/chat.php`
      (all api/v1/chats/* routes in one place)
- [x] Create `Modules/Chat/Routes/provider.php`
- [x] Create `Modules/Chat/Routes/dashboard.php`
- [x] Register all routes in `RouteServiceProvider` (locale + dashboard/provider prefixes)
- [x] Remove old chat routes from `routes/Api/V1/catalog.php`
      (kept opportunity + guarantor requires)
- [x] Remove old chat routes from `routes/dashboard.php`
      (kept index, show, openChat, updateStatus on SupportController)
- [x] Remove old provider chat routes from `routes/provider.php`
      (kept Inertia `provider.chat.index` on old ChatController until Phase 12)
- [x] Verify `php artisan route:list` shows all routes intact (20 api/v1/chats/* routes)
- [x] Run tests — Opportunity (81) and Guarantor (147) pass

### Completed: 2026-06-19
### Summary: Wired member/order/ticket API routes to `Modules/Chat` V1 controllers. Provider JSON routes (store/show/send + orders/*) point to module controllers; Inertia chat index stays on `Provider\ChatController`. Dashboard `support.tickets.send` points to `SupportChatController` with Inertia redirect preserved. Opportunity/guarantor routes unchanged in `catalog.php`.

---

## Phase 12 — Cleanup
- [x] Update all imports to `Modules\Chat\` namespaces
- [x] Move exceptions → `Modules/Chat/Exceptions/`
- [x] DELETE deprecated alias files (features, events, jobs, notifications, resources, requests, contracts, facade, enum)
- [x] DELETE `app/Http/Controllers/Api/V1/ChatController.php`
- [x] DELETE `app/Http/Controllers/Api/V1/OrderChatController.php`
- [x] DELETE `app/Http/Controllers/Api/V1/TicketSupportChatController.php`
- [x] DELETE `app/Http/Controllers/Provider/OrderChatController.php`
- [x] Trim `Provider\ChatController` to Inertia `index()` only
- [x] Remove `send()` from `SupportController` (now in `SupportChatController`)
- [x] DELETE `app/Http/Resources/Dashboard/Chat/`
- [x] DELETE `Modules/Opportunity/Policies/ConversationPolicy.php` (alias)
- [x] DELETE `Modules/Guarantor/Policies/ConversationPolicy.php` (alias)
- [x] Remove legacy `IChatService` binding from `ChatServiceProvider`
- [x] Keep `class_alias` for `NotifyChatMessageReceiver` (queue safety until production drains)
- [x] Keep `app/Services/Chat/Events/NewNotificationSendEvent.php` + `ProviderLocationChangedEvent.php` (non-chat, relocate in future)
- [x] Update dashboard `Show.tsx` → `SupportChatController` (Wayfinder)
- [x] Run Opportunity (81) + Guarantor (147) tests — pass
- [ ] `npm run build` — fails on pre-existing missing Catalog Wayfinder action (unrelated)

### Completed: 2026-06-19
### Summary: Removed all deprecated `@deprecated` alias files and old API/Provider chat controllers. Exceptions moved to module. `Provider\ChatController` retained for Inertia index only. `app/Services/Chat/` reduced to two non-chat broadcast events. Queue `class_alias` kept per deployment safety guidance.

---

## Phase 13 — Fix Bugs & Typos
- [x] `lastMassage` → `lastMessage` in Conversation model and ALL references
- [x] `last_massage_at` → `last_message_at` in resources and events (backward-compat `last_massage_at` kept)
- [x] `ChatUerException` → `ChatUserException` (rename file + class)
- [x] `reseriverDoesNotExist` → `receiverDoesNotExist` in exceptions
- [x] `replay` → `reply` in MemberChat; `replayAsAdmin`/`replayAsSupportable` → `replyAs*` in SupportChat
- [x] Fix `ConversationAttachment::chat()` wrong FK — added `message()` with correct FK
- [x] Fix `ConversationMessage` missing `SoftDeletes` trait
- [x] Fix `NotifyChatMessageReceiver` type hint `User` → `HasConversation`
- [x] Fix `OrderChat::getReceiver()` hardcoded user1/user2 assumption
- [x] Fix `UserController` unread count using `sender` instead of `receiver`
- [x] Delete `SupportChatUpdatedEvent` — already removed in Phase 2
- [x] Remove `Supportable` contract — already removed in Phase 2
- [x] Run tests — Opportunity (81) + Guarantor (147) pass

### Completed: 2026-06-19
### Summary: Fixed relation typo (`lastMessage`), API field backward compat for `last_massage_at`, exception renames, reply method names, attachment FK, SoftDeletes trait, job type hints, OrderChat receiver logic, and UserController unread count.

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
