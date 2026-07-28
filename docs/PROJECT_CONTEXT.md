# PROJECT CONTEXT

**Last verified: 2026-07-27, post-Settings/Reviews/Otp/Notification consolidations**

This is the entry-point map of the Ijaz codebase after the modularization and cleanup session. For endpoint/model/enum detail, use the specialized docs listed below — do not duplicate them here.

---

## 1 — How To Use This Documentation

| Doc | Purpose |
|---|---|
| **[docs/API_INVENTORY.md](API_INVENTORY.md)** | All **151** `api/*` routes: method, URI, controller namespace, auth, FormRequest, Resources |
| **[docs/MODELS_REFERENCE.md](MODELS_REFERENCE.md)** | All **72** Eloquent models (App Core + Modules), fields, relations, traits, enum casts |
| **[docs/ENUMS_REFERENCE.md](ENUMS_REFERENCE.md)** | All **31** enums, cases, backing types, model cast usage, utility traits |
| **[.cursor/rules/layered-architecture.mdc](../.cursor/rules/layered-architecture.mdc)** | **Authoritative** Controller → Service → Action → Repository / DTO / FormRequest rules |
| **[docs/DEFERRED_MOBILE_BREAKING_CHANGES.md](DEFERRED_MOBILE_BREAKING_CHANGES.md)** | Mobile-breaking items deliberately deferred until post-MVP (15/8) |
| **[modules_statuses.json](../modules_statuses.json)** | Enabled nwidart modules (all **16** currently `true`) |

---

## 2 — Project Overview

Ijaz is a multi-actor marketplace: users create service orders, providers respond with offers, both actors chat and settle via payments/wallets, plus guarantor (escrow) requests, support tickets, job postings, classified advisements (cars / property / electronics / institutes), opportunity listings, platform settings, and polymorphic reviews.

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

**OTP:** Unified `App\Models\Otp` (UUID) + `App\Enums\Auth\OtpPurposeEnum` via `HasOTPs` / `HasOTPsContract`. Deleted models `VerificationCode` / `RegisterVerificationCode` must not be resurrected.

---

## 4 — Directory Structure

### Top-level layout

```
app/                 — Core actors, Auth domains, Dashboard aggregator, Support utilities
Modules/             — 16 domain modules (nwidart), each with own Http/Services/Actions/…
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
├── Actions/           — Admin, Auth (Admin/Provider/User), Account, Dashboard, Provider, User
├── Console/Commands/
├── Contracts/         — Admin, Auth, Provider, QueryFilters, Selects, User, …
├── DTOs/              — Admin, Auth, Account, Dashboard, Provider, User
├── Enums/             — App-level enums (+ Auth/OtpPurposeEnum, Utilities traits)
├── Events/            — e.g. User domain events
├── Exceptions/Auth/
├── Helpers/
├── Http/
│   ├── Controllers/   — Api (+ V1 leftovers: Account, Otp, Platform, User auth), Dashboard, Frontend, General, Provider
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/            — 6 core actors: Admin, User, Provider, Employee, BlockHistory, Otp
├── Notifications/     — DomainNotification shared base only (domain subclasses live in modules)
├── NotificationChannel/ — FirebaseChannel, EventChannel
├── Providers/
├── Repositories/      — Admin, Auth, Account, Provider, User
├── Rules/
├── Services/
│   ├── Admin/ · Auth/ · Account/ · Dashboard/ · Provider/ · User/
│   ├── Firebase/      — Push notifications
│   └── Translations/  — Locale rendering for frontend
├── Support/           — Shared utilities: Normalize, Phone, HasNormalizedAttributes, …
├── Traits/            — Cross-cutting model traits (HasWallet, HasOTPs, Blockable, …)
└── UserProviders/     — Custom auth user providers
```

**Intentionally gone from `app/` (do not resurrect):** `app/Actions/Payment`, `app/Services/Normalize`, `app/Services/Sms`, `app/Guards`, `app/Observers`, `app/Jobs`, domain Order notification classes under old `app/Notifications/{Provider,User}`, `lib/` (including dead WhatsApp scaffolding), `VerificationCode` / `RegisterVerificationCode` models, `Setting` / `Review` models (moved to modules). Payment → `Modules/Payment`; SMS → `Modules/Sms`; Normalize/Phone → `app/Support/`; Order observers + Order notifications → `Modules/Orders`; Settings → `Modules/Settings`; Reviews → `Modules/Reviews`.

### Modules (`modules_statuses.json` — all enabled)

| Module | Purpose (one line) |
|---|---|
| **Catalog** | Car/property/device/electronic/specialization lookup tables + Dashboard CRUD + shared QueryFilters |
| **Chat** | Member / order / ticket conversations, realtime events, chat handlers |
| **Classifieds** | Car / property / electronics / institute advisements (API + Dashboard); shared advisement Actions |
| **Cms** | Banners, pages, FAQ questions, contact messages |
| **Geo** | Regions, cities, nationalities (Dashboard + Api/V1 resources) |
| **Guarantor** | Escrow/guarantor requests, installments, guarantor chat + notifications |
| **Jobs** | Job offers / listings API |
| **Marketplace** | Service categories, skills, provider types (catalog endpoints) |
| **Opportunity** | Opportunity listings, offers, comments, opportunity chat + notifications |
| **Orders** | User/provider/dashboard order flows, offers, payment hooks, observers, notifications |
| **Payment** | Gateways (PayTabs, Rajhi, testing), callbacks/webhooks, payment pipeline |
| **Reviews** | Polymorphic reviews; Dashboard CRUD; nested on Provider/User API resources |
| **Settings** | Platform settings (`Setting` + `SettingGroupEnum`); public `GET /api/v1/catalog/settings` |
| **Sms** | SMS gateway drivers (Authentica, etc.) — no HTTP surface of its own |
| **Support** | Ticket support API + Dashboard |
| **Wallet** | Balance, top-up, withdraw, transactions |

Typical module layout: `Actions/`, `Services/`, `Repositories/`, `Contracts/`, `DTOs/`, `Http/`, `Models/`, `Notifications/` (where applicable), `Routes/` (often `V1/` + `dashboard.php` / `provider.php`), `Providers/`, `tests/`.

### Routes

| File / area | Role |
|---|---|
| `routes/api.php` | API entry; mounts app + module API route files |
| `routes/api/V1/auth.php`, `platform.php` | Remaining app-owned API groups (OTP + `/auth/*` account/notifications + `/user/auth` in auth.php; catalog `providers` in platform.php) |
| Module `Routes/V1/api.php` | Domain API (including Settings `catalog/settings`, Geo catalog lookups) |
| `routes/dashboard.php` + module dashboard routes | Admin Inertia |
| `routes/provider.php` + module provider routes | Provider web |
| `routes/channels.php` | Broadcast channel auth |

---

## 5 — Architecture (mandatory)

**Authoritative rule:** [`.cursor/rules/layered-architecture.mdc`](../.cursor/rules/layered-architecture.mdc)

```
Controller → Service → Action → Repository / DTO / Contracts
                              ↘ FormRequest (validation only)
```

Skill shortcut: `.claude/skills/layered-architecture/`. Explicit `use` imports even for same-namespace classes (Pint-safe alias pattern when needed).

Shared notification shape: `App\Notifications\DomainNotification` — used by Orders / Guarantor / Opportunity. Chat `NewMessageSentNotification` stays independent.

---

## 6 — Conventions worth remembering

- Prefer FormRequests; avoid inline controller validation except rare documented leftovers.
- Prefer Resources over leaking Eloquent models / ad-hoc arrays from API controllers.
- Never mix raw `response()->json()` and `HasApiResponse` in the same controller style without reason.
- Catalog translation search: `TranslationSearchFilter` (`normalize: true` on `normalized_*`, `false` on raw `name` for brand/type).
- Classifieds advisement services share owner-auth / delete / delete-media Actions.

### Never do this

- ❌ Business logic or Eloquent queries in Controllers
- ❌ Eloquent queries in Services (use Repositories)
- ❌ FormRequest / Request objects inside Actions
- ❌ Hardcoded status strings when an enum exists
- ❌ “Fix” deferred typos (`last_massage_at`) without mobile + migration plan — see Known Issues
- ❌ Reintroduce deleted `lib/` Payment/SMS/WhatsApp scaffolding or deleted OTP models
- ❌ Put domain notification subclasses back under `app/Notifications/{Provider,User}`

---

## 7 — Known Issues (current / deferred)

> The old “Known Issues” list in pre-extraction docs is **resolved** (recoverable from git history if needed).

**Open mobile-breaking work lives in [docs/DEFERRED_MOBILE_BREAKING_CHANGES.md](DEFERRED_MOBILE_BREAKING_CHANGES.md)** — revisit after MVP ship (**15/8**). Do not land these on v1 without mobile coordination / versioning.

| # | Item | Why deferred |
|---|---|---|
| 1 | Chat `last_massage_at` typo key (alongside correct `last_message_at`) | Old clients may still read the typo |
| 2 | Pagination shape fragmentation (flat `BaseCollection` vs nested Chat `paginate` ± page URLs) | Unifying breaks list screens |
| 3 | Wallet `add-balance` leaks `PaymentInitResult` fields | Clients may depend on exposed keys |
| 4 | `POST /api/v1/otp/verify` `type=phone` still returns `success: false` (side-effect persists) | UI may key off `success` |
| 5 | Phone OTP response semantics | Consolidated into #4 |

Also still true (non-breaking quirks, documented in models/API docs):

- Some notification / account-mutation endpoints still use `GET` (verb debt).
- Geo catalog lookups (nationalities, regions, cities) live on `Modules\Geo\Http\Controllers\Api\V1\GeoController`; platform `providers` on `App\Http\Controllers\Api\V1\PlatformController`; public settings on `Modules\Settings\Http\Controllers\Api\V1\SettingController`.
- **`PropertyCategoryTranslation.normalized_title` is never written on save** — column + filter exist (`TranslationSearchFilter` on `normalized_title`), but no model hook populates it (peer translations do). PropertyCategory Arabic-normalized search stays broken until a separate save-path fix.
- **CarBrand / CarType / PropertyType lack `normalized_*` translation columns** — search correctly uses raw `name` (`normalize: false`). Adding Arabic-insensitive search needs a future schema + save-hook pass, not filter-side fake normalization.

**Planned but not implemented (not a bug, not dead code to remove):**

- **`Employee`** (`App\Models\Employee`) — model + `employee` guard/provider in `config/auth.php` exist as groundwork for future staff management. No migration, routes, controller, or CRUD. One live consumer: `Modules\Marketplace\Models\Category` checks `auth('employee')` (guard resolution is fine; no employee can log in yet). Build-out later must follow layered architecture. See the model docblock.

---

## 8 — Session summary (how we got here)

This cleanup / modularization effort turned a mostly-monolithic `app/` into a **16-module** Laravel app with a consistent layering rule. High-level outcomes through 2026-07-27:

1. **Module extraction** — Chat, Geo, Jobs, Cms, Catalog, Support, Marketplace, Orders, Settings, Reviews (plus Payment, Wallet, Sms, Guarantor, Opportunity, Classifieds) under `Modules/*`.
2. **`Api/V1` convention** — Mobile/shared API standardized; Geo/Settings catalog endpoints in their modules; contract-freeze tests lock response shapes (Jobs, Cms, Catalog, Geo, Orders, UserResource, Reviews, DomainNotification, TranslationSearchFilter).
3. **Shared utilities** — `Normalize` / `Phone` → `app/Support/`; dead `lib/` scaffolding removed.
4. **Dashboard / Auth layering** — Admin/User/Provider management and Auth domains follow Controller → Service → Action → Repository in `app/`.
5. **Architecture rule** — `.cursor/rules/layered-architecture.mdc` always-on; Pint-safe same-namespace import alias pattern documented.
6. **OTP unification** — Single `App\Models\Otp` + `OtpPurposeEnum`; `VerificationCode` / `RegisterVerificationCode` deleted.
7. **Catalog / Classifieds DRY** — transactional file-upload concern; shared Classifieds Actions; shared `TranslationSearchFilter` + `ParentFilter`.
8. **Notifications** — Order notifications moved to `Modules/Orders/Notifications`; shared `DomainNotification` base for Orders/Guarantor/Opportunity (Chat excluded).
9. **Docs regen** — `MODELS_REFERENCE` (72), `ENUMS_REFERENCE` (31), `API_INVENTORY` (151 `api/*`), and this `PROJECT_CONTEXT` rewritten from live sources (not memory).
10. **Other polish** — Unified Inertia `Errors/ErrorPage` + i18n; deferred mobile-breaking items captured rather than silently “fixed.”

For exact inventory numbers and shapes, prefer the three reference docs over this summary.

---

## Appendix — Quick reference

### Config worth knowing

- `config/app.php` — name, locale, timezone
- `config/auth.php` — guards / providers
- `config/broadcasting.php` — Reverb
- `config/firebase.php` — push
- `config/otp.php` — OTP TTLs by purpose
- `Modules/Payment/config` + app payment config — drivers / PayTabs
- `Modules/Sms/config` — SMS gateways
- `modules_statuses.json` — which modules are enabled

### Core models still in `app/Models`

`Admin`, `User`, `Provider`, `Employee`, `BlockHistory`, `Otp`

(Domain models such as Order, Conversation, Payment, Wallet, GuarantorRequest, Setting, Review, advisements, JobOffer, etc. live under their modules — see MODELS_REFERENCE.)

### When You Need to Update This File

| Change | Update |
|---|---|
| New module added / enabled, or a domain moves between `app/` and `Modules/` | This file §4 + `modules_statuses.json` |
| Layering rule or exception established | **Only** [`.cursor/rules/layered-architecture.mdc`](../.cursor/rules/layered-architecture.mdc) (link from here if needed) |
| New / changed API endpoint or response shape | [docs/API_INVENTORY.md](API_INVENTORY.md) (regen from `route:list --json` preferred) |
| Model / relation / trait / enum cast change | [docs/MODELS_REFERENCE.md](MODELS_REFERENCE.md) |
| Enum cases or new enum | [docs/ENUMS_REFERENCE.md](ENUMS_REFERENCE.md) |
| Deferred mobile item fixed or newly deferred | [docs/DEFERRED_MOBILE_BREAKING_CHANGES.md](DEFERRED_MOBILE_BREAKING_CHANGES.md) + Known Issues §7 here |
| Auth guard / actor model change | This file §3 |

Keep this file as the **map**, not a second copy of the inventories. When in doubt, regenerate the specialized docs from the live codebase and only refresh the sections here that would otherwise go stale (structure, modules table, known issues, session summary).
