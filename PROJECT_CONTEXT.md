# PROJECT CONTEXT

**Last verified: 2026-07-26, post-full-module-extraction**

This is the entry-point map of the Ijaz codebase after the modularization and cleanup session. For endpoint/model/enum detail, use the specialized docs listed below — do not duplicate them here.

---

## 1 — How To Use This Documentation

| Doc | Purpose |
|---|---|
| **[docs/API_INVENTORY.md](docs/API_INVENTORY.md)** | All **149** `api/*` routes: method, URI, controller namespace, auth, FormRequest, Resources |
| **[docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md)** | All **73** Eloquent models (App Core + Modules), fields, relations, traits, enum casts |
| **[docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md)** | All **29** enums, cases, backing types, model cast usage, utility traits |
| **[.cursor/rules/layered-architecture.mdc](.cursor/rules/layered-architecture.mdc)** | **Authoritative** Controller → Service → Action → Repository / DTO / FormRequest rules |
| **[docs/DEFERRED_MOBILE_BREAKING_CHANGES.md](docs/DEFERRED_MOBILE_BREAKING_CHANGES.md)** | Mobile-breaking items deliberately deferred until post-MVP (15/8) |
| **[modules_statuses.json](modules_statuses.json)** | Enabled nwidart modules (all 14 currently `true`) |

---

## 2 — Project Overview

Ijaz is a multi-actor marketplace: users create service orders, providers respond with offers, both actors chat and settle via payments/wallets, plus guarantor (escrow) requests, support tickets, job postings, classified advisements (cars / property / electronics / institutes), and opportunity listings.

**Main actors**

| Actor | Model | Primary auth |
|---|---|---|
| User | `App\Models\User` | Sanctum `user-api` (mobile) + session `web` |
| Provider | `App\Models\Provider` | Session `provider` + Sanctum-shared API |
| Admin | `App\Models\Admin` | Session `admin` (dashboard) |
| Employee | `App\Models\Employee` | Session `employee` — **planned, not implemented** (no migration/routes/CRUD; see §7) |

**Tech stack (from manifests)**

- PHP `^8.2` · Laravel `^13` · Sanctum `^4` · Reverb `^1` · Wayfinder
- Inertia Laravel `^3` · React `^19` · `@inertiajs/react` `^3` · TailwindCSS `^4` · Vite `^6` · TypeScript `^5`
- Modules: `nwidart/laravel-modules` `^13`
- Notable: Spatie Permission + MediaLibrary, mcamara localization, MMAE ApiResponse, PayTabs, Astrotomic Translatable, Dedoc Scramble, Pest `^4`

**App metadata**

- Name: `Ijaz` · Locale: `en` · Timezone: `UTC`
- Broadcast: `reverb` · Queue: `database` · Filesystem: `public`
- Payment default driver: `paytabs` (see `Modules/Payment`)

---

## 3 — Authentication & Guards

| Guard | Model | Driver | Used by |
|---|---|---|---|
| `web` | User | session | Frontend / shared web |
| `admin` | Admin | session | `routes/dashboard.php` |
| `employee` | Employee | session | Planned staff auth — not implemented (see §7 / `Employee` model docblock) |
| `provider` | Provider | session | `routes/provider.php` |
| `user-api` | User | sanctum | Mobile-exclusive `/api/v1/user/*` (+ abilities) |
| `sanctum` | Generic | sanctum | Shared API (chats, jobs, wallet, tickets, advisements, OTP, …) |

**Broadcasting:** `routes/channels.php` private channels (`user-{id}`, `provider-{id}`, `admin-{id}`, `chats.{chat}`, …). API also mounts `Broadcast::routes(['middleware' => ['auth:sanctum']])`.

---

## 4 — Directory Structure

### Top-level layout

```
app/                 — Core actors, Auth domains, Dashboard aggregator, Support utilities
Modules/             — 14 domain modules (nwidart), each with own Http/Services/Actions/…
routes/              — App-level web/dashboard/provider/api entry + Api/V1 leftovers
resources/js/        — Inertia React pages, Wayfinder-generated actions/routes
docs/                — Reference docs + deferred mobile changes + archive
tests/               — Pest feature/unit (plus per-module `Modules/*/tests`)
.cursor/rules/       — Always-on architecture rule (layered-architecture.mdc)
```

### What remains in `app/`

Core platform code that is **not** a product domain module (or not yet fully extracted):

```
app/
├── Actions/           — Admin, Auth (Admin/Provider/User), Dashboard, Provider, User
├── Console/Commands/
├── Contracts/         — Admin, Auth, OTPS, Provider, QueryFilters, Selects, User
├── DTOs/              — Admin, Auth, Dashboard, Provider, User
├── Enums/             — Remaining app-level enums (+ Utilities traits)
├── Events/            — e.g. User domain events
├── Exceptions/Auth/
├── Helpers/
├── Http/
│   ├── Controllers/   — Api (+ V1 leftovers), Dashboard, Frontend, General, Provider
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/            — 9 core actors/settings (Admin, User, Provider, Employee, …)
├── Notifications/     — User / Provider notifications still owned by app
├── Providers/
├── Repositories/      — Admin, Auth, Provider, User
├── Rules/
├── Services/
│   ├── Admin/ · Auth/ · Dashboard/ · Provider/ · User/
│   ├── Firebase/      — Push notifications
│   └── Translations/  — Locale rendering for frontend
├── Support/           — Shared utilities: Normalize, Phone, HasNormalizedAttributes, …
├── Traits/            — Cross-cutting model traits (HasWallet, HasOTPs, Blockable, …)
└── UserProviders/     — Custom auth user providers
```

**Intentionally gone from `app/` (do not resurrect):** `app/Actions/Payment`, `app/Services/Normalize`, `app/Services/Sms`, `app/Guards`, `app/Observers`, `app/Jobs`, `lib/` (including dead WhatsApp scaffolding). Payment → `Modules/Payment`; SMS → `Modules/Sms`; Normalize/Phone → `app/Support/`; Order observers → `Modules/Orders`.

### Modules (`modules_statuses.json` — all enabled)

| Module | Purpose (one line) |
|---|---|
| **Catalog** | Car/property/device/electronic/specialization lookup tables + Dashboard CRUD |
| **Chat** | Member / order / ticket conversations, realtime events, chat handlers |
| **Classifieds** | Car / property / electronics / institute advisements (API + Dashboard) |
| **Cms** | Banners, pages, FAQ questions, contact messages |
| **Geo** | Regions, cities, nationalities (Dashboard + Api/V1 resources) |
| **Guarantor** | Escrow/guarantor requests, installments, guarantor chat |
| **Jobs** | Job offers / listings API |
| **Marketplace** | Service categories, skills, provider types (catalog endpoints) |
| **Opportunity** | Opportunity listings, offers, comments, opportunity chat |
| **Orders** | User/provider/dashboard order flows, offers, payment hooks, observers |
| **Payment** | Gateways (PayTabs, Rajhi, testing), callbacks/webhooks, payment pipeline |
| **Sms** | SMS gateway drivers (Authentica, etc.) — no HTTP surface of its own |
| **Support** | Ticket support API + Dashboard |
| **Wallet** | Balance, top-up, withdraw, transactions |

Typical module layout: `Actions/`, `Services/`, `Repositories/`, `Contracts/`, `DTOs/`, `Http/`, `Models/`, `Routes/` (often `V1/` + `dashboard.php` / `provider.php`), `Providers/`, `tests/`.

### Routes

| File / area | Role |
|---|---|
| `routes/api.php` | API entry; mounts app + module API route files |
| `routes/Api/V1/user.php`, `catalog.php` | Remaining app-owned API groups (user auth, geo/settings leftovers) |
| `Modules/*/Routes/V1/*` | Module API (orders, chat, wallet, jobs, …) |
| `routes/web.php` | Frontend / public / auth |
| `routes/dashboard.php` | Admin Inertia dashboard aggregator |
| `routes/provider.php` | Provider Inertia dashboard |
| `routes/channels.php` | Reverb channel authorization |

---

## 5 — Architecture Patterns

### Mandatory layering

**Authoritative source:** [`.cursor/rules/layered-architecture.mdc`](.cursor/rules/layered-architecture.mdc). Do not invent a parallel explanation — follow that file.

```
Controller → Service → Action → Repository / DTO / Contracts
                              ↘ FormRequest (validation only)
```

Summary only:

1. Controllers: FormRequest in → one Service call → HTTP out. No business logic / Eloquent.
2. Services: orchestrate Actions (+ transactions). No Eloquent / HTTP shaping.
3. Actions: one `handle()` unit of work; may compose other Actions; return DTOs.
4. Repositories: **only** layer that talks to Eloquent (via `*RepositoryInterface`).
5. DTOs: `final readonly class` with promoted props between layers.
6. FormRequests: validation only.

**Reference implementations**

| Pattern | Where |
|---|---|
| Simplest full chain | `app/Services/Auth/AdminAuthService` + `LoginAdminAction` + `AdminRepository` |
| Multi-action service | `app/Services/Auth/UserAuthService` + `app/Actions/Auth/User/*` |
| Driver / strategy service | `Modules/Payment/Services/PaymentService` + `Gateways/*` |
| Same driver pattern | `Modules/Sms/Services/SmsService` + `Gateways/*` |
| Domain modules | Geo, Orders, Chat, Wallet, etc. under `Modules/*` |

Also note the rule’s **explicit `use` + Pint alias** guidance (same-namespace imports must use a distinct alias so Pint does not strip them).

### Cross-cutting patterns still in force

- **API envelope:** `MMAE\ApiResponse` / `HasApiResponse` (`successResponse`, `successMessageResponse`, `failedMessageResponse`, …). Shapes per endpoint: [API_INVENTORY.md](docs/API_INVENTORY.md).
- **Payments:** initiate via domain controllers → Payment module gateway → callback/webhook updates payment + wallet side-effects.
- **Realtime:** Reverb; chat events live under `Modules/Chat` (and related module handlers for guarantor/opportunity/support).
- **Media:** Spatie MediaLibrary on orders, jobs, advisements, guarantor, opportunities.
- **i18n:** Astrotomic on translatable models; flat JSON in `lang/{en,ar,hi,ur}.json`; `TranslationServices` for frontend bundles. Inertia error pages use the unified `Errors/ErrorPage` component.

---

## 6 — Conventions & Rules

### Naming

- Prefer enums over raw status strings (`OrderStatusEnum`, payment/wallet enums in modules, …).
- FormRequests end with `Request`; API Resources with `Resource` / `Collection`.
- Module namespaces: `Modules\{Name}\…` — always explicit `use` statements.

### Validation & responses

- Prefer FormRequests; avoid inline controller validation except rare documented leftovers.
- Prefer Resources over leaking Eloquent models / ad-hoc arrays from API controllers.
- Never mix raw `response()->json()` and `HasApiResponse` in the same controller style without reason.

### Never do this

- ❌ Business logic or Eloquent queries in Controllers
- ❌ Eloquent queries in Services (use Repositories)
- ❌ FormRequest / Request objects inside Actions
- ❌ Hardcoded status strings when an enum exists
- ❌ “Fix” deferred typos (`PropertiyCategory`, `last_massage_at`) without mobile + migration plan — see Known Issues
- ❌ Reintroduce deleted `lib/` Payment/SMS/WhatsApp scaffolding

---

## 7 — Known Issues (current / deferred)

> The old “Known Issues” list in pre-extraction docs is **resolved** (recoverable from git history if needed).

**Open work lives in [docs/DEFERRED_MOBILE_BREAKING_CHANGES.md](docs/DEFERRED_MOBILE_BREAKING_CHANGES.md)** — revisit after MVP ship (**15/8**). Do not land these on v1 without mobile coordination / versioning.

| # | Item | Why deferred |
|---|---|---|
| 1 | Chat `last_massage_at` typo key (alongside correct `last_message_at`) | Old clients may still read the typo |
| 2 | Pagination shape fragmentation (flat `BaseCollection` vs nested Chat `paginate` ± page URLs) | Unifying breaks list screens |
| 3 | Wallet `add-balance` leaks `PaymentInitResult` fields | Clients may depend on exposed keys |
| 4 | `POST /api/v1/otp/verify` `type=phone` still returns `success: false` (side-effect persists) | UI may key off `success` |
| 5 | `PropertiyCategory` / `propertiy_categories` systemic rename | Schema + code migration epic |
| 6 | Phone OTP response semantics | Consolidated into #4 |

Also still true (non-breaking quirks, documented in models/API docs):

- Some notification / account-mutation endpoints still use `GET` (verb debt).
- Geo catalog lookups (nationalities, regions, cities) live on `Modules\Geo\Http\Controllers\Api\V1\GeoController`; platform misc (`providers`, `settings`) on `App\Http\Controllers\Api\V1\PlatformController` — Marketplace/Cms/Catalog modules own the rest of `/api/v1/catalog/*`.

**Planned but not implemented (not a bug, not dead code to remove):**

- **`Employee`** (`App\Models\Employee`) — model + `employee` guard/provider in `config/auth.php` exist as groundwork for future staff management. No migration, routes, controller, or CRUD. One live consumer: `Modules\Marketplace\Models\Category` checks `auth('employee')` (guard resolution is fine; no employee can log in yet). Build-out later must follow layered architecture. See the model docblock.

---

## 8 — Session summary (how we got here)

This cleanup / modularization session turned a mostly-monolithic `app/` into a **14-module** Laravel app with a consistent layering rule. High-level outcomes:

1. **Module extraction** — Chat, Geo, Jobs, Cms, Catalog, Support, Marketplace, Orders (plus existing Payment, Wallet, Sms, Guarantor, Opportunity, Classifieds) live under `Modules/*` with their own routes, services, actions, repositories, and tests.
2. **`Api/V1` convention** — Mobile/shared API standardized; Geo Api resources relocated into `Modules/Geo`; contract-freeze tests lock response shapes for Jobs, Cms, Catalog, Geo, Orders, UserResource.
3. **Shared utilities consolidation** — `Normalize` / `Phone` → `app/Support/`; dead `lib/WhatsApp` + `Lib\` PSR-4 removed.
4. **Dashboard / Auth layering** — Admin/User/Provider management and Auth domains follow Controller → Service → Action → Repository in `app/` (Pass D / E1 / E2 style work).
5. **Architecture rule** — `.cursor/rules/layered-architecture.mdc` is always-on; Pint-safe same-namespace import alias pattern documented.
6. **Docs regen** — `MODELS_REFERENCE` (73), `ENUMS_REFERENCE` (29), `API_INVENTORY` (149 `api/*`), and this `PROJECT_CONTEXT` rewritten from live sources (not memory).
7. **Other polish** — Unified Inertia `Errors/ErrorPage` + i18n; deferred mobile-breaking items captured rather than silently “fixed.”

For exact inventory numbers and shapes, prefer the three reference docs over this summary.

---

## Appendix — Quick reference

### Config worth knowing

- `config/app.php` — name, locale, timezone
- `config/auth.php` — guards / providers
- `config/broadcasting.php` — Reverb
- `config/firebase.php` — push
- `Modules/Payment/config` + app payment config — drivers / PayTabs
- `Modules/Sms/config` — SMS gateways
- `modules_statuses.json` — which modules are enabled

### Core models still in `app/Models`

`Admin`, `User`, `Provider`, `Employee`, `Setting`, `Review`, `BlockHistory`, `VerificationCode`, `RegisterVerificationCode`

(Domain models such as Order, Conversation, Payment, Wallet, GuarantorRequest, advisements, JobOffer, etc. live under their modules — see MODELS_REFERENCE.)

### When You Need to Update This File

| Change | Update |
|---|---|
| New module added / enabled, or a domain moves between `app/` and `Modules/` | This file §4 + `modules_statuses.json` |
| Layering rule or exception established | **Only** [`.cursor/rules/layered-architecture.mdc`](.cursor/rules/layered-architecture.mdc) (link from here if needed) |
| New / changed API endpoint or response shape | [docs/API_INVENTORY.md](docs/API_INVENTORY.md) (regen from `route:list --json` preferred) |
| Model / relation / trait / enum cast change | [docs/MODELS_REFERENCE.md](docs/MODELS_REFERENCE.md) |
| Enum cases or new enum | [docs/ENUMS_REFERENCE.md](docs/ENUMS_REFERENCE.md) |
| Deferred mobile item fixed or newly deferred | [docs/DEFERRED_MOBILE_BREAKING_CHANGES.md](docs/DEFERRED_MOBILE_BREAKING_CHANGES.md) + Known Issues §7 here |
| Auth guard / actor model change | This file §3 |

Keep this file as the **map**, not a second copy of the inventories. When in doubt, regenerate the specialized docs from the live codebase and only refresh the sections here that would otherwise go stale (structure, modules table, known issues, session summary).
