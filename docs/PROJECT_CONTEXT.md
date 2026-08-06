# PROJECT CONTEXT

**Last verified: 2026-08-05, post flat Catalog + Skill LookupCache extension (`feature/project-wide-caching`)**

This is the entry-point map of the Ijaz codebase after the modularization and cleanup session. For endpoint/model/enum detail, use the specialized docs listed below — do not duplicate them here.

---

## 1 — How To Use This Documentation

| Doc | Purpose |
|---|---|
| **[docs/API_INVENTORY.md](API_INVENTORY.md)** | All **151** `api/*` routes: method, URI, controller namespace, auth, FormRequest, Resources |
| **[docs/MODELS_REFERENCE.md](MODELS_REFERENCE.md)** | All **72** Eloquent models (App Core + Modules), fields, relations, traits, enum casts |
| **[docs/ENUMS_REFERENCE.md](ENUMS_REFERENCE.md)** | All **31** enums, cases, backing types, model cast usage, utility traits |
| **[.cursor/rules/layered-architecture.mdc](../.cursor/rules/layered-architecture.mdc)** | **Authoritative** Controller → Service → Action → Repository / DTO / FormRequest rules |
| **This file §9** | LookupCache Tier 1 / Tier 2 keys, invalidation, TTL, and deliberately deferred items |
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

**Admin bootstrap:** Do **not** seed a root admin with a hardcoded password. After `php artisan migrate --seed` (roles via `RolePermissionSeeder`), create admins with:

```bash
php artisan admin:create
```

Root accounts get `root=true` (Gate::before bypass) and `super-admin` when that role exists. Non-root accounts must pick an existing admin-guard role; the command fails clearly if roles were never seeded.

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
│   ├── Firebase/      — Stateless FCM sender (OutgoingFirebaseMessage + Http facade); config under services.firebase
│   └── Translations/  — Locale rendering for frontend
├── Support/           — Shared utilities: Normalize, Phone, HasNormalizedAttributes, LookupCache, …
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
- **Normalized search (canonical):** use `App\Support\TranslationSearch` (or Catalog `TranslationSearchFilter` with `normalize: true`) against `normalized_*` columns on **both** Dashboard and API/select paths. Exception: CarBrand / CarType / PropertyType / ProviderType / Cms Pages & Questions lack `normalized_*` — those keep raw `name`/`title` (`normalize: false`).
- **Seeders for normalized_*-bearing tables MUST use Eloquent models** (never `DB::table()->insert()` into translation/advisement tables that rely on saving hooks). Enforced by `tests/Feature/SeedersMustNotRawInsertNormalizedTablesTest.php`.
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
- ~~**`PropertyCategoryTranslation.normalized_title` is never written on save**~~ — **RESOLVED** on `fix/property-category-normalized-title`: saving hook matches Car/Device/Specialization; one-time migration backfills existing NULL rows.
- ~~**Geo Regions/Cities/Nationalities search returned zero for seeded data**~~ — **RESOLVED**: seeders now use Eloquent; `TranslationSearch` unifies Dashboard+API; CI blocks raw inserts into normalized_*-bearing tables.
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

## 9 — Lookup / stats caching (Tier 1 + Tier 2)

Shipped on `feature/project-wide-caching`. **Do not re-audit from scratch** or invent a second cache layer — extend `App\Support\LookupCache` and the tables below.

### Utility — `App\Support\LookupCache`

| Concern | Rule |
|---|---|
| Entry point | `app/Support/LookupCache.php` — forever / TTL / locale / scoped remember + `forget*` / `flush` |
| Storage prefix | Logical keys become `lookup:{key}` (plus `:{locale}` / `:{scopeId}` when scoped) |
| Registry | Tracked-key registry (`lookup:__registry__`) so granular forget works on the **database** cache driver (no tags required) |
| Type preservation | Return the closure’s **natural** type. Eloquent / Support collections and models must be allow-listed in `config/cache.php` → `serializable_classes`. Prefer arrays/DTOs when unsure |
| Where to wrap | Repository (or the single shared list Action) — Controllers/Services stay thin |
| Tests | `tests/Unit/Support/LookupCacheTest.php` + per-domain `*LookupCacheTest.php` (cold vs warm equality + query-count drop) |
| Test hygiene | `Tests\TestCase` flushes LookupCache between tests; `TestingDatabaseGuard` aborts if config is cached or DB is not sqlite `:memory:` |
| Test runner | Default `composer test` = Pest Parallel (`--processes=8 --exclude-group=serial`). Quarantined race test: `composer test:serial`. Full coverage: `composer test:all` (see README) |

```php
// Forever — invalidate only via forget*()
LookupCache::rememberForever('settings:public', fn () => ...);
LookupCache::rememberForeverForLocale('regions:all', $locale, fn () => ...);
LookupCache::rememberForeverScoped('cities:by-region', $locale, $regionId, fn () => ...);

// TTL — Tier 2 stats (pure expiry; no write-path forget)
LookupCache::rememberFor('stats:orders:dashboard', 30, fn () => ...);

// Invalidation (Tier 1 write Actions only)
LookupCache::forget('settings:public');
LookupCache::forgetAllLocales('regions:all');
LookupCache::forgetScopedAllLocales('cities:by-region', $regionId);
```

**Spatie permissions** use Spatie’s own `spatie.permission.cache` (24h, store `default`) for the global permission↔role graph. That is **not** LookupCache. Per-request `getAllPermissions()` / `can()` still load the admin’s role/permission pivots (~2–3 queries) — expected; frontend `usePermissions()` reads Inertia `auth.permissions` (0 DB). Do not duplicate Spatie with LookupCache unless a measured problem appears.

**Frontend i18n** bundles `resources/js/lang/*.json` via Vite/i18next — not a per-request DB/file cache target. `TranslationService`’s `rememberForever('translations.{locale}')` is dead code behind an early return; `app.blade.php` does not call `render()`.

### Tier 1 — forever + write-path invalidation

Mostly-static lookups. Invalidate in Store/Update/Delete (and status toggles when present).

| Domain | Logical key(s) | Remember API | Invalidate at |
|---|---|---|---|
| Settings (public API) | `settings:public` | `rememberForever` | `UpdateSettingsAction` |
| ProviderTypes | `provider-types:all` (+ locale) | `rememberForeverForLocale` | Store/Update/Delete ProviderType Actions |
| Regions | `regions:all`, `regions:dropdown` (+ locale) | `rememberForeverForLocale` | Store/Update/Delete Region Actions |
| Cities | `cities:by-region` (+ locale + region id; `0` = all) | `rememberForeverScoped` | Store/Update/Delete City (+ Region delete clears scoped keys) |
| Nationalities | `nationalities:all` (+ locale) | `rememberForeverForLocale` | Store/Update/Delete Nationality Actions |
| CMS Banners | `banners:all` | `rememberForever` | Store/Update/Delete Banner Actions |
| CMS Pages | `pages:all` (+ locale), `pages:single` (+ locale + slug) | `rememberForeverForLocale` / `rememberForeverScoped` | Store/Update/Delete Page Actions |
| CMS Questions | `questions:all` (+ locale) | `rememberForeverForLocale` | Store/Update/Delete Question Actions |
| CarBrand | `car-brands:all` (+ locale) | `rememberForeverForLocale` | Store/Update/Delete/UpdateStatus CarBrand Actions (+ delete also clears `car-types:by-brand` for that brand + `0`) |
| CarType | `car-types:by-brand` (+ locale + `car_brand_id`; `0` = all) | `rememberForeverScoped` | Store/Update/Delete/UpdateStatus CarType Actions |
| PropertyType | `property-types:all` (+ locale) | `rememberForeverForLocale` | Store/Update/Delete/UpdateStatus PropertyType Actions |
| ElectronicBrand | `electronic-brands:all` (+ locale; **active only**) | `rememberForeverForLocale` | Store/Update/Delete/UpdateStatus ElectronicBrand Actions (select + API `getAll` share this key) |
| Skill | `skills:by-category` (+ locale + `category_id`; always filters that id, including `0`) | `rememberForeverScoped` | Store/Update/Delete Skill Actions |

Keep `regions:all` (`listForSelect`) and `regions:dropdown` (`getAllForDropdown`) as **separate** keys — Resource / shape differences.

Empty-search only: filled `search` bypasses LookupCache (same as Geo cities).

### Tier 2 — short TTL, no invalidation

Brief staleness is acceptable for badges / summary dashboards. **Do not** add `LookupCache::forget` on order/user/provider write paths for these keys.

| Domain | Logical key | TTL | Wrapped method |
|---|---|---|---|
| Orders | `stats:orders:dashboard` | 30s | `OrderRepository::dashboardStats()` |
| Guarantor | `stats:guarantor:dashboard` | 30s | `GuarantorRepository::getDashboardStats()` |
| Users | `stats:users:status-counts` | 30s | `UserManagementRepository::statusCounts()` |
| Providers | `stats:providers:status-counts` | 30s | `ProviderManagementRepository::statusCounts()` |
| PanAnalytics | `stats:pan-analytics:all` | 60s | `PanAnalyticsRepository::all()` (feeds summary / categories / topElements / funnel; paginate stays live) |

### Deliberately deferred (do not treat as forgotten)

**Original Tier 1 leftover**

| Item | Why deferred |
|---|---|
| `DashboardHomeService::forHome()` (`stats:admin:home`) | Composite DTO + Eloquent graphs; needs allow-list growth; optional 60s TTL later |

**Original Tier 2 Catalog / Marketplace leftovers (partially done)**

| Item | Why deferred |
|---|---|
| Marketplace Category trees / ajax / paginated select / API nested children | High key surface (`search` × `parent_id` × `per_page` × optional `provider_type_id`) + nested eager load — not a single flat forever key |
| CarCategory / PropertyCategory / DeviceCategory / Specialization selects & API | Hierarchical flat-vs-roots shape split still needs deliberate key design (not forgotten — follow-up after Category design) |
| Catalog **paginated** API indexes (car-brands, car-types, property-types, …) | Page/search variants; form selects are the high-value path (now cached for flat domains above) |

**Done from that original list (do not re-investigate):** CarBrand, CarType (by brand), PropertyType, ElectronicBrand (active), Skill (by category).

**Original Tier 3 — skip**

| Item | Why skipped |
|---|---|
| Wallet / order lists / chat / OTP / profiles / guarantor apps | Stale data = wrong money or privacy bugs |
| Opportunity API `offers_count` | Viewer-scoped; global cache would leak |
| Dashboard paginated CRUD indexes | Low traffic; high filter cardinality |
| Category/Ajax **search** result pages | Key explosion + weak hit rate |
| Role `loadCount('users')` | Low-traffic admin |
| HTTP/CDN response caching | Premature without Redis + purge story |
| Provider home per-provider stats | Needs `provider:{id}` keys; low priority |
| `translations.{locale}` / Spatie admin pivots | File I/O dead path / expected per-request pivots — not LookupCache |

### Optional next phase (when justified by traffic)

1. Marketplace Category **roots-only, empty search** (± provider-type scope) — still not full tree/ajax/search.
2. Hierarchical Catalog selects (Car/Property/Device categories, Specializations) once roots vs flat-all keys are decided.
3. Short-TTL `DashboardHomeService::forHome` if admin home remains hot.
4. Switch `CACHE_STORE` to Redis later for cheaper tags; registry already works on database driver.

---

## Appendix — Quick reference

### Config worth knowing

- `config/app.php` — name, locale, timezone
- `config/auth.php` — guards / providers
- `config/broadcasting.php` — Reverb
- `config/cache.php` — default store + `serializable_classes` allow-list for LookupCache
- `config/services.php` → `firebase` — FCM credentials path, OAuth cache key, token TTL skew, endpoints
- Push path: `DomainNotification` / Chat `NewMessageSentNotification` → `FirebaseChannel` → `FirebaseService::send(OutgoingFirebaseMessage)` (stateless; no mutable fluent state)
- **Decision point:** live FCM payload is `notification` + `data` only. A former unused `notify()` path also set `android.priority` / APNs headers — never called from the channel; re-add only if mobile needs it.
- `config/otp.php` — OTP TTLs by purpose
- Spatie permission cache — `config('permission.cache')` (package default; key `spatie.permission.cache`)
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
| New LookupCache domain, key, TTL, or deferred-item decision | This file **§9** (and `config/cache.php` allow-list if caching new Eloquent types) |

Keep this file as the **map**, not a second copy of the inventories. When in doubt, regenerate the specialized docs from the live codebase and only refresh the sections here that would otherwise go stale (structure, modules table, known issues, session summary, caching §9).
