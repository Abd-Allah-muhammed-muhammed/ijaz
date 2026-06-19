# PROJECT CONTEXT

Last verified from source: 2026-04-16

---

## Section 1 — How To Use This Documentation

This file is the entry point for understanding the project architecture and patterns. For detailed information, refer to the following specialized documentation files:

- **[docs/API_INVENTORY.md](docs/API_INVENTORY.md)** — All endpoints with request/response details, controllers, FormRequest classes, and resources
- **[docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md)** — All models with fields, relationships, traits, enums, and notable features
- **[docs/REFACTOR_NOTES.md](docs/REFACTOR_NOTES.md)** — Known issues, refactor priorities, and future improvements
- **[docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md)** — All enums with their cases, values, and usage patterns

---

## Section 2 — Project Overview

This application is a multi-actor marketplace/platform built around service orders, offers, payments, wallets, guarantor requests, support tickets, jobs, and classified advisements for properties and cars. The route and model layers show these product areas clearly: users create and manage orders, providers receive offers and notifications, both actors can chat, the platform exposes catalog/look-up data, and there are payment flows for order settlement, guarantor requests, and wallet top-ups/withdrawals.

**Main actors:**
- **User:** `App\Models\User`, authenticated in the API by the `user-api` Sanctum guard.
- **Provider:** `App\Models\Provider`, authenticated in provider web sessions and also used in Sanctum-protected API flows.
- **Admin:** `App\Models\Admin`, authenticated through the dashboard session guard.

**Tech stack from manifests:**
- PHP `^8.2`
- Laravel Framework `^13.0`
- Laravel Sanctum `^4.0`
- Inertia Laravel `^3.0`
- React `^19.0.0`
- `@inertiajs/react` `^3.0.2`
- TailwindCSS `^4.1.4`
- Vite `^6.4.2`
- TypeScript `^5.7.2`
- Notable packages: `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `mcamara/laravel-localization`, `mmae/apiresponse`, `paytabscom/laravel_paytabs`, `laravel/reverb`, `laravel/wayfinder`, `ratchet/pawl`, `astrotomic/laravel-translatable`

**Verified app metadata from config/env:**
- App name: `Ijaz`
- Locale: `en`
- Timezone: `UTC`
- Default DB connection in `.env.example`: `sqlite`
- Broadcast driver: `reverb`
- Queue driver: `database`
- Filesystem disk: `public`
- Payment driver default: `paytabs`

---

## Section 3 — Authentication & Guards

| Guard | Model | Driver | Used By | Token/Session |
|---|---|---|---|---|
| `web` | `User` | `session` | `routes/web.php`, `routes/dashboard.php` (admin), `routes/provider.php` | Classic browser session |
| `admin` | `Admin` | `session` | Dashboard routes in `routes/dashboard.php` | Browser session (admin dashboard) |
| `employee` | `Employee` | `session` | Not visibly mounted in analyzed routes | Session auth |
| `provider` | `Provider` | `session` | Provider dashboard routes in `routes/provider.php` | Browser session |
| `user-api` | `User` | `sanctum` | `/api/v1/user/*` routes for login/register/profile/orders | Short-lived Sanctum tokens + ability checks |
| `sanctum` | Generic | `sanctum` | Chats, jobs, guarantor, tickets, wallet, OTP, notifications, advisements | Generic Sanctum tokens; controller code branches on model type |

**Broadcast authentication:**
- `bootstrap/app.php` registers broadcasting auth middleware as `web` + `auth:admin,provider`
- `routes/channels.php` defines private channels: `provider-{id}`, `user-{id}`, `admin-{id}`, `online`, `public`, `systems.{id}`, `chats.{chat}`, `category.{category}`
- `routes/api.php` also registers `Broadcast::routes(['middleware' => ['auth:sanctum']]);`

---

## Section 4 — Directory Structure

**Main source tree (annotated):**

```
app/
├── Actions/Payment/          — Payment pipeline actions (orders, guarantor, top-ups, gateway updates)
├── Console/Commands/         — CLI commands including JS enum generation
├── Contracts/                — Interfaces (OTPs, Selects)
├── Enums/                    — Status/lookup enums with utility traits
├── Events/                   — Domain events (e.g., NewOrderCreated)
├── Guards/                   — Custom authentication guards
├── Helpers/                  — Helper functions
├── Http/
│   ├── Controllers/          — API, dashboard, provider, frontend, payments controllers
│   ├── Middleware/           — JSON, localization, appearance, Inertia middleware
│   └── Requests/             — FormRequest validation classes
│   └── Resources/            — API and dashboard serialization resources
├── Jobs/                     — (likely empty or not discovered)
├── Models/                   — Domain models (users, orders, chats, payments, taxonomy, advisements)
├── Notifications/            — Order/provider notifications
├── Observers/                — Order and OrderOffer lifecycle observers
├── Services/                 — Chat, Firebase, Normalize, SMS, Translations services
├── Traits/                   — Reusable behavior traits (HasWallet, HasOTPs, Blockable, etc.)
└── UserProviders/            — Custom service providers

bootstrap/
└── app.php                   — Registers routing, middleware aliases, broadcasting

config/                       — app, auth, broadcasting, cache, database, firebase, mail, payment, queue, etc.

database/
├── migrations/               — Schema history (many versioned as separate create/alter migrations)
└── seeders/                  — Categories, regions, settings, demo orders, admin permissions

lib/                          — Custom wrappers around external services
├── Payment/                  — PayTabs gateway integration
├── SMS/                      — SMS provider integration
└── WhatsApp/                 — WhatsApp messaging integration

routes/
├── api.php                   — Main API routes v1
├── Api/V1/*.php              — V1 endpoint groups (users, orders, chats, jobs, etc.)
├── web.php                   — Frontend/auth routes
├── dashboard.php             — Admin dashboard routes
├── provider.php              — Provider dashboard routes
└── channels.php              — WebSocket broadcast channels
```

**Non-standard notes:**
- `app/Http/Requests/Api/Api/User` has a duplicated `Api` segment in the namespace.
- `PropertiyCategory` is misspelled throughout the codebase.
- Route groups are split between `routes/api.php` and `routes/Api/V1/*.php` and mounted in `bootstrap/app.php`.

---

## Section 5 — Architecture Patterns

### Controller Structure (Thin Controllers Rule)
- Controllers validate input via FormRequest classes
- Controllers call a Service or Action for business logic
- Controllers return responses via `HasApiResponse` trait helpers
- **Rule:** No business logic inside controllers; keep them thin

### Services vs. Actions
- **Services:** Used when logic touches more than one model (e.g., `ChatService`, `FirebaseService`, `TranslationServices`)
- **Actions:** Used for single, self-contained operations (e.g., payment status updates, transaction recording)
- **Directory:** `app/Services/` for services, `app/Actions/` for actions

### Payment Flow
1. User/Provider initiates payment via `/api/v1/*/pay` endpoint
2. Controller calls payment action → creates Payment record → redirects to gateway
3. Gateway calls callback endpoint → status action updates Payment + wallet transactions
4. Real-time notification broadcast via WebSocket

### Real-Time Communication (Reverb)
- Broadcast driver: `reverb` (configured in `config/broadcasting.php`)
- Channel auth in `routes/channels.php`
- Events dispatched from Chat service: `NewMessageEvent`, `ChatUpdatedEvent`, `SupportChatUpdatedEvent`
- Jobs: `NotifyChatMessageReceiver` queued for async notification delivery

### File Uploads
- Spatie MediaLibrary used on: orders, jobs, advisements, guarantor requests
- Manual storage via `store('...')` for other uploads; path persisted in models
- Media routes protected: `/media/file/{media}`, `/media/{media}`, `/media/chat/{media}`

### Translations
- Locale-aware model translation via Astrotomic translatable traits and `withTranslation()` query helpers
- Translation rendering via `TranslationServices::render()`
- Supported locales: en, ar, hi, ur (from `lang/` directory)

---

## Section 6 — Services & Actions

### Key Services

| Service | File | Purpose | Key Methods |
|---|---|---|---|
| `ChatService` | `app/Services/Chat/ChatService.php` | Orchestrates chat creation, feature selection, online lookup | `generate()`, `order()`, `support()`, `members()`, `onlineUsers()` |
| `FirebaseService` | `app/Services/Firebase/FirebaseService.php` | Sends Firebase push notifications | `message()`, `data()`, `target()`, `toTopic()`, `notify()` |
| `Normalize` | `app/Services/Normalize/Normalize.php` | Normalizes text for search/storage across locales | `make()`, `toString()` |
| `Phone` | `app/Services/Sms/Phone.php` | Formats and validates phone numbers | `make()`, `placeholder()`, `isValid()`, `toString()` |
| `TranslationServices` | `app/Services/Translations/TranslationServices.php` | Renders locale translations into frontend output | `render()`, `getTranslationsFor()` |

### Key Actions

| Action | File | Purpose | Touches |
|---|---|---|---|
| `UpdatePaymentStatus` | `app/Actions/Payment/PayTabs/UpdatePaymentStatus.php` | Maps PayTabs responses to payment updates | Payment model, payment status |
| `ProcessOrder` | `app/Actions/Payment/Order/ProcessOrder.php` | Finalizes order/offer state after payment | Order, OrderOffer models |
| `AddUserTransaction` | `app/Actions/Payment/Order/AddUserTransaction.php` | Records wallet transaction for user | WalletTransaction model |
| `ProcessTopUpRequest` | `app/Actions/Payment/TopUp/ProcessToUpRequest.php` | Finalizes top-up request after payment | TopUpRequest, Wallet models |

---

## Section 7 — Conventions & Rules

### Naming Conventions
- Enums are heavily used for state (e.g., `OrderStatusEnum`, `ProviderStatusEnum`, `PaymentMethodEnum`)
- Resource/controller names mirror model names
- No hardcoded status strings; always use corresponding enum from `app/Enums/`
- FormRequest classes end with `Request` (e.g., `LoginRequest`, `OrderRequest`)
- Resource classes end with `Resource` (e.g., `UserResource`, `OrderResource`)

### API Response Format
- Use `HasApiResponse` trait helpers exclusively: `successResponse()`, `successMessageResponse()`, `successResponseWithToken()`, `failedMessageResponse()`
- Never mix raw `response()->json()` with `HasApiResponse` in the same controller
- Response shapes vary by endpoint; see [docs/API_INVENTORY.md](docs/API_INVENTORY.md) for specific shapes

### Validation
- Always use dedicated `FormRequest` classes; never validate inline in controllers
- Exception: rare cases in API endpoints for simple checks (see `OrderChatController@store`)

### Traits & Reusability
- Core traits live in `app/Traits/` (e.g., `Blockable`, `HasWallet`, `HasOTPs`)
- Traits are mixed into models to add standard behavior
- See [docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md) for trait details

### Database & Migrations
- Many tables are versioned as separate `add_cols_*` migrations rather than single creates
- Polymorphic relationships use `user_id`/`user_type` for flexible actor references
- UUID primary keys on order-related tables; bigint on user/provider tables

### Never Do This
- ❌ Hardcode status strings (use `OrderStatusEnum::New->value`)
- ❌ Use inline validation in controllers (use FormRequest classes)
- ❌ Return raw arrays/models from API endpoints (use Resource classes)
- ❌ Put business logic in controllers
- ❌ Mix raw `response()->json()` with `HasApiResponse` trait calls
- ❌ Create new typos or perpetuate existing ones (`walletTTransactions`, `PropertiyCategory`, `lastMassage`)

---

## Section 8 — Known Issues Summary

Full issue list is in [docs/REFACTOR_NOTES.md](docs/REFACTOR_NOTES.md).

### Top Priorities

1. **Order & Guarantee payment/status flows** (High)
   - Controller mixes business rules, fee calculations, payment initiation, and media handling
   - Status transition logic is embedded in controllers instead of services
   - Suggestion: Extract to dedicated services or action classes

2. **Duplicated filtering logic** (Medium)
   - `CarAdvisementController` and `PropertyAdvisementController` duplicate the same filter shape for `index()` and `all()`
   - Suggestion: Move filter logic into shared query scopes or filter classes

3. **Request namespace & model name issues** (Medium)
   - `app/Http/Requests/Api/Api/User/` has duplicated `Api` segment
   - `PropertiyCategory` is misspelled throughout
   - `Conversation::lastMassage` should be `lastMessage`
   - Suggestion: Refactor namespace to `Api/V1/User/`; rename models if cost is acceptable

### Additional Risks
- Response shapes are not uniform across endpoints (wallet/payment endpoints use custom arrays vs. resources elsewhere)
- Chat endpoints use inline validation instead of FormRequest classes
- Notification and account deletion endpoints use `GET` for state-changing operations (should use `PATCH`/`DELETE`)

---

## Appendix: Quick Reference

### Important Config Files
- `config/app.php` — App name (Ijaz), locale (en), timezone (UTC)
- `config/auth.php` — Guard definitions (user-api, sanctum, admin, provider, employee, web)
- `config/broadcasting.php` — Reverb broadcast driver setup
- `config/firebase.php` — Firebase credentials and settings
- `config/payment.php` — Payment driver and PayTabs settings
- `config/sms.php` — SMS provider (Authentica) configuration
- `.env.example` — Default environment settings

### Core Models
- `User`, `Provider`, `Admin`, `Employee` — Actor models
- `Order`, `OrderOffer`, `OrderStatusHistory` — Order workflow
- `GuarantorRequest` (Modules/Guarantor) — Escrow/guarantor requests
- `Payment`, `Wallet`, `WalletTransaction`, `TopUpRequest`, `WithdrawRequest` — Financial tracking
- `Conversation`, `ConversationMessage`, `ConversationAttachment` — Chat
- `TicketSupport` — Support tickets
- `PropertyAdvisement`, `CarAdvisement` — Classified listings
- `JobOffer` — Job postings

### Key Routes Files
- `routes/api.php` — Main API entry point
- `routes/Api/V1/` — Endpoint groups for users, orders, chats, jobs, etc.
- `routes/web.php` — Frontend/public pages and authentication
- `routes/dashboard.php` — Admin dashboard (mounted under `/{locale}/dashboard`)
- `routes/provider.php` — Provider dashboard (mounted under `/{locale}/provider`)
- `routes/channels.php` — WebSocket broadcast channels

---

## When You Need to Update This File

Add a new entry to [docs/REFACTOR_NOTES.md](docs/REFACTOR_NOTES.md) if:
- A model, endpoint, or enum is added
- A pattern or convention changes
- A known issue is resolved or new issues arise

Update [docs/API_INVENTORY.md](docs/API_INVENTORY.md) if:
- A new API endpoint is added or modified
- Request/response shapes change

Update [docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md) if:
- A new model is created or modified
- Model relationships, traits, or enums change

Update [docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md) if:
- A new enum is added or cases are modified
