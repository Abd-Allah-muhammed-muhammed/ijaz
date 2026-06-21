# Wallet Module — Todo List

> Branch: feature/wallet-module
> Last updated: 2026-06-21
> Goal: Move ALL wallet-related code into Modules/Wallet/ — completely self-contained.
>       If someone deletes Modules/Wallet/, no wallet code should remain anywhere else.
>
> Pattern: Controller → Service → Action → Repository
> Golden Rule: API response shapes do NOT change — only backend moves.

---

## Dependency Map

```
app/Enums/OperationStatusEnum     ← stays in app/ (used by TicketSupport too)
app/Models/User                   → uses Modules\Wallet\Traits\HasWallet
app/Models/Provider               → uses Modules\Wallet\Traits\HasWallet

Modules/Wallet/
  ├── Models/Wallet
  ├── Models/WalletTransaction
  ├── Models/TopUpRequest         → uses App\Enums\OperationStatusEnum ✅
  ├── Models/WithdrawRequest      → uses App\Enums\OperationStatusEnum ✅
  ├── Traits/HasWallet
  └── Services/WalletService      ← ONLY place that mutates wallet balance

Modules/Payment/  → uses Modules\Wallet\Services\WalletService
Modules/Guarantor/ → uses Modules\Wallet\Services\WalletService
```

---

## Phase 1 — Module Scaffold
- [x] Create module: `php artisan module:make Wallet`
- [x] Configure `module.json` and `composer.json`
- [x] Create `WalletServiceProvider` + `RouteServiceProvider`
- [x] Register in `modules_statuses.json`
- [x] Create full directory structure with .gitkeep:
```
Modules/Wallet/
├── Actions/
├── Contracts/
│   └── Repositories/
├── Database/
│   └── Migrations/
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
│       └── Dashboard/
├── Models/
├── Policies/
├── Repositories/
├── Routes/
│   ├── V1/
│   ├── provider.php
│   └── dashboard.php
├── Services/
└── Traits/
```
- [x] Remove scaffold artifacts (app/, config/, resources/, etc.)
- [x] `composer dump-autoload`
- [x] Verify: `php artisan module:list` shows Wallet enabled

### Completed: 2026-06-21
### Summary: Created `Modules/Wallet/` with Opportunity-style providers at `Providers/` (not `app/Providers/`). Configured `module.json`, module-level `composer.json` (PSR-4 `./`), empty route stubs (`Routes/V1/wallet.php`, `provider.php`, `dashboard.php`), and full directory tree with `.gitkeep`. Removed nwidart scaffold (`app/`, `config/`, `resources/`, `routes/`, `database/seeders`, `package.json`, `vite.config.js`). Registered `"Wallet": true` in `modules_statuses.json`. `RouteServiceProvider::mapApiRoutes()` uses a compatible signature with `BaseModuleRouteServiceProvider` and loads `wallet.php` instead of `api.php`.

---

## Phase 2 — Move Models

Move from `app/Models/` → `Modules/Wallet/Models/`:
- [x] `Wallet.php` → `Modules/Wallet/Models/Wallet.php`
- [x] `WalletTransaction.php` → `Modules/Wallet/Models/WalletTransaction.php`
- [x] `TopUpRequest.php` → `Modules/Wallet/Models/TopUpRequest.php`
- [x] `WithdrawRequest.php` → `Modules/Wallet/Models/WithdrawRequest.php`

For each model:
- Update namespace to `Modules\Wallet\Models`
- Update all imports across codebase
- Leave backward-compat alias in old location:
```php
// app/Models/Wallet.php
namespace App\Models;
/** @deprecated Use Modules\Wallet\Models\Wallet */
class Wallet extends \Modules\Wallet\Models\Wallet {}
```

Move `HasWallet` trait:
- [x] `app/Traits/HasWallet.php` → `Modules/Wallet/Traits/HasWallet.php`
- [x] Fix typo: `walletTTransactions()` → `walletTransactions()`
- [x] Update namespace to `Modules\Wallet\Traits`
- [x] Update imports in `User.php` and `Provider.php`
- [x] Leave backward-compat alias in `app/Traits/HasWallet.php`

Run tests after — 315 must pass.

### Completed: 2026-06-21
### Summary: Moved `Wallet`, `WalletTransaction`, `TopUpRequest`, and `WithdrawRequest` to `Modules/Wallet/Models/` with namespace `Modules\Wallet\Models`. Moved `HasWallet` to `Modules/Wallet/Traits/` and renamed `walletTTransactions()` → `walletTransactions()` across 10 consumer files. Updated imports in payment actions, controllers, resources, and Guarantor actions. Backward-compat aliases remain in `app/Models/` and `app/Traits/`. `OperationStatusEnum` stays in `app/Enums/`. All 323 module tests pass (Guarantor 153, Opportunity 81, Chat 89).

---

## Phase 3 — Move Migrations

- [x] Move all wallet migrations to `Modules/Wallet/Database/Migrations/`:
  - `2025_05_23_220424_create_wallets_table.php`
  - `2025_07_01_175711_create_top_up_requests_table.php`
  - `2025_08_21_193400_create_payments_table.php` ← stays in app/ (Payment module will own it)
  - `2025_08_27_210054_add_cols_to_wallet_transactions.php`
  - `2025_08_28_202050_add_cols_to_wallets.php`
  - `2025_09_12_224428_add_payment_driver_to_top_up_requests.php`
  - `2025_09_23_174450_create_withdraw_requests_table.php`
- [x] Register migrations path in `WalletServiceProvider::boot()`
- [x] Verify `php artisan migrate:status` shows all migrations

### Completed: 2026-06-21
### Summary: Moved 6 wallet migrations from `database/migrations/` to `Modules/Wallet/Database/Migrations/` (payments migration left in app/). Registered path via `loadMigrationsFrom()` in `WalletServiceProvider::boot()`. All 6 migrations show as `Ran` in `migrate:status`. All 323 module tests pass.

---

## Phase 4 — Fix Schema (New Migrations)

Create new migrations in `Modules/Wallet/Database/Migrations/`:

- [x] Add `wallets.credit` column (in fillable but missing from DB)
- [x] Add unique index on `wallets(user_id, user_type)` — prevent duplicate wallets
- [x] Add index on `top_up_requests.status`
- [x] Add index on `withdraw_requests.status`
- [x] Add index on `wallet_transactions(wallet_id, created_at)`
- [x] Add `wallet_transactions.payment_id` nullable string (audit trail to Payment)
- [x] Add defaults (0) on `wallet_transactions.pending_credit` and `pending_debit`
- [x] Add `withdraw_requests.payment_driver` nullable string (referenced in WithdrawController)
- [ ] Fix `down()` methods on broken alter migrations
- [x] Run `php artisan migrate` locally and verify

### Completed: 2026-06-21
### Summary: Added 6 new migrations (`2026_06_21_000001`–`000006`): `wallets.credit`, unique index on `(user_id, user_type)`, status/wallet indexes, `wallet_transactions.payment_id`, pending column defaults, and `withdraw_requests.payment_driver` + `transaction_id`. All migrations ran successfully. Existing migration files left untouched. All 323 module tests pass.

---

## Phase 5 — Enums

- [x] Create `Modules\Wallet\Enums\TransactionTypeEnum`
```php
enum TransactionTypeEnum: string {
    case Credit        = 'credit';
    case Debit         = 'debit';
    case PendingCredit = 'pending_credit';
    case PendingDebit  = 'pending_debit';
}
```
Note: `OperationStatusEnum` stays in `app/Enums/` — used by TicketSupport too.

### Completed: 2026-06-21
### Summary: Created `TransactionTypeEnum` with four cases and a `label()` helper.

---

## Phase 6 — DTOs

- [x] Create `Modules\Wallet\DTOs\WalletBalanceData`
```php
readonly class WalletBalanceData {
    public function __construct(
        public float $balance,
        public float $pending_credit,
        public float $pending_debit,
        public float $available,  // balance - pending_debit
    ) {}
}
```

- [x] Create `Modules\Wallet\DTOs\WalletTransactionData`
```php
readonly class WalletTransactionData {
    public function __construct(
        public float  $amount,
        public string $description,
        public string $operation_type,
        public string $operation_id,
        public TransactionTypeEnum $type,
        public float  $credit      = 0,
        public float  $debit       = 0,
        public float  $pending_credit = 0,
        public float  $pending_debit  = 0,
        public ?string $payment_id = null,
    ) {}
}
```

### Completed: 2026-06-21
### Summary: Created `WalletBalanceData` with `fromWallet()` factory and `WalletTransactionData` with typed properties including `balance_before`, `balance_after`, and optional `payment_id`.

---

## Phase 7 — Exceptions

- [x] Create `Modules\Wallet\Exceptions\WalletException`
- [x] Create `Modules\Wallet\Exceptions\InsufficientBalanceException extends WalletException`

### Completed: 2026-06-21
### Summary: Created base `WalletException` (422 default) and `InsufficientBalanceException` with available/requested amounts in the message. All 323 module tests pass.

---

## Phase 8 — Repositories

- [x] Create `WalletRepositoryInterface` → `Modules/Wallet/Contracts/Repositories/`
```php
interface WalletRepositoryInterface {
    public function findOrCreate(Model $owner): Wallet;
    public function lockForUpdate(Model $owner): Wallet;
}
```

- [x] Create `WalletTransactionRepositoryInterface`
```php
interface WalletTransactionRepositoryInterface {
    public function create(Wallet $wallet, WalletTransactionData $data): WalletTransaction;
    public function listForOwner(Model $owner, int $perPage = 15): LengthAwarePaginator;
}
```

- [x] Create `WalletRepository` → `Modules/Wallet/Repositories/`
- [x] Create `WalletTransactionRepository`
- [x] Bind both in `WalletServiceProvider::register()`

### Completed: 2026-06-21
### Summary: Created `WalletRepository` and `WalletTransactionRepository` with interfaces. Repository bindings registered in `WalletServiceProvider`.

---

## Phase 9 — Actions

Each action:
- Uses repositories (no direct model mutations)
- Does NOT start its own DB transaction (runs inside caller's transaction)
- Creates a `WalletTransaction` ledger row via repository

- [x] `CreditWalletAction` — `balance += amount`
- [x] `DebitWalletAction` — `balance -= amount` (throws InsufficientBalanceException if insufficient)
- [x] `AddPendingCreditAction` — `pending_credit += amount`
- [x] `AddPendingDebitAction` — `pending_debit += amount`
- [x] `ReleasePendingCreditToBalanceAction` — `pending_credit -= gross`, `balance += net` (gross - fees)
- [x] `ReversePendingDebitAction` — `pending_debit -= amount`
- [x] `AdjustPendingAction` — `pending_credit += X`, `pending_debit -= Y` (single SQL — replaces AddProviderTransaction)
- [x] `FinalizeWithdrawAction` — reverse pending + optional debit (replaces WithdrawRequestController logic)

Run tests after — 315 must pass.

### Completed: 2026-06-21
### Summary: Created all 8 wallet actions. Each locks wallet via repository, mutates balance/pending fields, and records ledger rows through `WalletTransactionRepository`. `FinalizeWithdrawAction` delegates to `ReversePendingDebitAction` and `DebitWalletAction`.

---

## Phase 10 — WalletService

THE only place that touches wallet balance. All other code uses this service.

- [x] Create `Modules\Wallet\Services\WalletService`

Methods (all run inside caller's DB transaction):
```php
public function credit(Model $owner, float $amount, Model $operation, string $description = ''): void
public function debit(Model $owner, float $amount, Model $operation, string $description = ''): void
public function addPendingCredit(Model $owner, float $amount, Model $operation, string $description = ''): void
public function addPendingDebit(Model $owner, float $amount, Model $operation, string $description = ''): void
public function releasePendingCreditToBalance(Model $owner, float $gross, float $net, Model $operation, string $description = ''): void
public function reversePendingDebit(Model $owner, float $amount, Model $operation, string $description = ''): void
public function adjustPending(Model $owner, float $creditDelta, float $debitDelta, Model $operation, string $description = ''): void
public function finalizeWithdraw(Model $owner, WithdrawRequest $request, bool $approved): void
public function canWithdraw(Model $owner, float $amount): bool
public function getBalance(Model $owner): WalletBalanceData
```

- [x] Bind `WalletService` in `WalletServiceProvider::register()`
- [x] Run ALL tests — 315 must pass

### Completed: 2026-06-21
### Summary: Created `WalletService` as the public API delegating to all 8 actions. Added `canWithdraw()` (available balance check with lock) and `getBalance()` (returns `WalletBalanceData`). Bound in `WalletServiceProvider`. Added `payment_id` to `WalletTransaction` fillable. All 323 module tests pass.

---

## Phase 11 — Resources

Move resources → `Modules/Wallet/Http/Resources/`:
- [x] `App\Http\Resources\Api\V1\TopUpResource` → `Modules\Wallet\Http\Resources\TopUpResource`
- [x] `App\Http\Resources\Api\V1\WithdrawRequestResource` → `Modules\Wallet\Http\Resources\WithdrawRequestResource`
      Fix bug: @mixin should be WithdrawRequest not TopUpRequest
- [x] `App\Http\Resources\Dashboard\TopUpResource` → `Modules\Wallet\Http\Resources\Dashboard\TopUpResource`
- [x] `App\Http\Resources\Dashboard\WithdrawResource` → `Modules\Wallet\Http\Resources\Dashboard\WithdrawResource`
      Fix bug: @mixin should be WithdrawRequest not TopUpRequest
- [x] Create `Modules\Wallet\Http\Resources\WalletResource`
- [x] Create `Modules\Wallet\Http\Resources\WalletTransactionResource`
- [x] Create `Modules\Wallet\Http\Resources\WalletTransactionCollection`
- [x] Leave backward-compat aliases in old locations
- [x] Update all imports across codebase

### Completed: 2026-06-21
### Summary: Moved 4 resources to module (API + Dashboard). Fixed `@mixin` bugs on `WithdrawRequestResource` and `WithdrawResource`. Created new `WalletResource`, `WalletTransactionResource`, and `WalletTransactionCollection` (extends `BaseCollection`). Backward-compat aliases remain in `app/Http/Resources/`. Updated controller imports. App V1 `WalletResource`/`WalletTransactionCollection` kept until controller move (Phase 13 in roadmap).

---

## Phase 12 — Requests

Move requests → `Modules/Wallet/Http/Requests/`:
- [x] `StoreTopUpRequest` → `Modules\Wallet\Http\Requests\StoreTopUpRequest`
- [x] `StoreWithdrawRequestRequest` → `Modules\Wallet\Http\Requests\StoreWithdrawRequest`
- [x] `UpdateTopUpRequestStatusRequest` → `Modules\Wallet\Http\Requests\Dashboard\UpdateTopUpStatusRequest`
- [x] `UpdateWithdrawRequestStatusRequest` → `Modules\Wallet\Http\Requests\Dashboard\UpdateWithdrawStatusRequest`
- [x] Leave backward-compat aliases

### Completed: 2026-06-21
### Summary: Moved 4 form requests to module. Renamed `StoreWithdrawRequestRequest` → `StoreWithdrawRequest` and dashboard status requests to `UpdateTopUpStatusRequest` / `UpdateWithdrawStatusRequest`. Backward-compat aliases in `app/Http/Requests/`. Updated all controller imports and type hints. Verified: `optimize:clear`, wallet routes intact, 323 tests pass.

---

## Phase 13 — V1 API Controller

- [x] Create `Modules\Wallet\Http\Controllers\V1\WalletController`
      Replaces: `App\Http\Controllers\Api\V1\WalletController`
      Methods: `balance`, `addBalance`, `withdraw`, `transactions`
      Fix: balance check uses `WalletService::canWithdraw()` with lock
      Fix: set `wallet_id` on TopUpRequest create
      Uses: WalletService only — no direct model mutations
- [x] Create `Modules/Wallet/Routes/V1/wallet.php` — same URLs, same names
- [x] Remove old routes from `routes/Api/V1/catalog.php`
- [x] Run tests

### Completed: 2026-06-21
### Summary: Created module V1 `WalletController` with `WalletService` + `WalletTransactionRepository`. Fixed `wallet_id` on top-up create, `canWithdraw()` + `addPendingDebit()` on withdraw. Routes moved to `Modules/Wallet/Routes/V1/wallet.php`. Extended `listForOwner()` with date filters.

---

## Phase 14 — Provider Controllers

- [x] Create `Modules\Wallet\Http\Controllers\Provider\TopUpController`
      Replaces: `App\Http\Controllers\Provider\TopUpController`
      Uses: WalletService
- [x] Create `Modules\Wallet\Http\Controllers\Provider\WithdrawController`
      Replaces: `App\Http\Controllers\Provider\WithdrawController`
      Fix: remove references to non-existent `transaction_id`/`payment_driver` on WithdrawRequest
      Fix: unreachable catch pattern
      Uses: WalletService
- [x] Create `Modules/Wallet/Routes/provider.php` — same URLs, same names
- [x] Remove old routes from `routes/provider.php`
- [x] Run tests

### Completed: 2026-06-21
### Summary: Moved provider TopUp/Withdraw controllers to module. Fixed unreachable catch blocks, `wallet_id` on top-up create, withdraw show no longer queries payment gateway. Withdraw uses `canWithdraw()`, `addPendingDebit()`, and `reversePendingDebit()`. Provider routes use Chat-style localization wrapper in `RouteServiceProvider`. All 323 tests pass.

---

## Phase 15 — Dashboard Controllers

- [x] Create `Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController`
      Replaces: `App\Http\Controllers\Dashboard\TopUpRequestController`
      Fix: remove orphaned `DB::commit()` without `beginTransaction()`
      Uses: `WalletService::credit()` instead of inline wallet mutation
- [x] Create `Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController`
      Replaces: `App\Http\Controllers\Dashboard\WithdrawRequestController`
      Uses: `WalletService::finalizeWithdraw()`
- [x] Create `Modules/Wallet/Routes/dashboard.php` — same URLs, same names
- [x] Remove old routes from `routes/dashboard.php`
- [x] Run tests

### Completed: 2026-06-21
### Summary: Moved dashboard TopUp/Withdraw controllers to module. Fixed orphaned `DB::commit()` bug with proper `DB::transaction()`. Offline approved top-ups use `WalletService::credit()`; withdraw status updates use `WalletService::finalizeWithdraw()`. Dashboard routes registered via localization wrapper in `RouteServiceProvider`. All 323 tests pass.

---

## Phase 16 — Update Payment Pipeline (use WalletService)

Replace ALL inline wallet mutations in payment actions with WalletService:
- [ ] `app/Actions/Payment/AddModelTransaction` → `WalletService::credit()` or `WalletService::addPendingDebit()`
- [ ] `app/Actions/Payment/Order/AddUserTransaction` → `WalletService::addPendingDebit()`
- [ ] `app/Actions/Payment/Order/AddProviderTransaction` → `WalletService::adjustPending()`
- [ ] `Modules/Guarantor/Actions/Payment/AddCounterpartyWalletTransaction` → `WalletService::addPendingCredit()`
- [ ] `Modules/Guarantor/Actions/Payment/AddRequesterWalletTransaction` → `WalletService::addPendingDebit()`
- [ ] `Modules/Guarantor/Actions/Installment/ReleaseInstallmentAction` → `WalletService::releasePendingCreditToBalance()`
- [ ] `Modules/Guarantor/Actions/Guarantor/EndGuarantorAction` → `WalletService::releasePendingCreditToBalance()`
      Fix bug: was releasing full pending_credit without deducting fees
- [ ] `Modules/Guarantor/Actions/Guarantor/CancelGuarantorAction` → `WalletService::reversePendingDebit()` + `WalletService::reversePendingCredit()` (if exists)
- [ ] Run ALL tests — 315 must pass

### Completed: —
### Summary: —

---

## Phase 17 — Cleanup

- [ ] Delete old controllers from `app/Http/Controllers/Api/V1/WalletController.php`
- [ ] Delete `app/Http/Controllers/Provider/TopUpController.php`
- [ ] Delete `app/Http/Controllers/Provider/WithdrawController.php`
- [ ] Delete `app/Http/Controllers/Dashboard/TopUpRequestController.php`
- [ ] Delete `app/Http/Controllers/Dashboard/WithdrawRequestController.php`
- [ ] Delete old resources from `app/Http/Resources/Api/V1/TopUpResource.php`
- [ ] Delete `app/Http/Resources/Api/V1/WithdrawRequestResource.php`
- [ ] Delete `app/Http/Resources/Dashboard/TopUpResource.php`
- [ ] Delete `app/Http/Resources/Dashboard/WithdrawResource.php`
- [ ] Delete old requests from `app/Http/Requests/Api/V1/StoreTopUpRequest.php`
- [ ] Delete `app/Http/Requests/Api/V1/StoreWithdrawRequestRequest.php`
- [ ] Delete `app/Http/Requests/Dashboard/UpdateTopUpRequestStatusRequest.php`
- [ ] Delete `app/Http/Requests/Dashboard/UpdateWithdrawRequestStatusRequest.php`
- [ ] Delete backward-compat model aliases from `app/Models/`
- [ ] Delete `app/Traits/HasWallet.php`
- [ ] Remove wallet bindings from `AppServiceProvider`
- [ ] Run: `php artisan route:list | grep wallet` — same routes as before
- [ ] `npm run build` — no errors
- [ ] Run ALL tests — 315 must pass

### Completed: —
### Summary: —

---

## Phase 18 — Tests

- [ ] Tests for `WalletService` — all 10 methods
- [ ] Tests for each Action
- [ ] Tests for `InsufficientBalanceException`
- [ ] Tests for `V1/WalletController` routes (balance, topup online, topup offline, withdraw, transactions)
- [ ] Tests for Provider TopUp routes
- [ ] Tests for Provider Withdraw routes
- [ ] Tests for Dashboard TopUp approval routes
- [ ] Tests for Dashboard Withdraw approval routes
- [ ] Tests for concurrent withdraw (race condition prevention)
- [ ] Regression: ALL 315 existing tests pass

### Completed: —
### Summary: —

---

## Bug Fixes Log

| # | Bug | Fixed in Phase |
|---|-----|----------------|
| 1 | `TopUpRequestController` — `DB::commit()` without `beginTransaction()` | Phase 15 |
| 2 | `WithdrawController::show()` — reads non-existent `transaction_id`, `payment_driver` | Phase 14 |
| 3 | `WithdrawRequestResource` — `@mixin TopUpRequest` wrong model | Phase 11 |
| 4 | `WithdrawResource (Dashboard)` — `@mixin TopUpRequest` wrong model | Phase 11 |
| 5 | `WalletController` — balance check without row lock on concurrent withdraws | Phase 13 |
| 6 | `HasWallet::walletTTransactions()` — typo (extra T) | Phase 2 |
| 7 | `EndGuarantorAction` — releases full pending_credit without deducting fees | Phase 16 |
| 8 | `wallets.credit` — in fillable but no DB column | Phase 4 |
| 9 | `TopUpRequest.wallet_id` — never set on create | Phase 13 |
| 10 | `wallet_transactions.pending_credit/debit` — no default value | Phase 4 |

---

## Notes
- WalletService is the ONLY place that mutates wallet balance — no direct model mutations anywhere else
- All actions run inside the CALLER's DB transaction — they never start their own
- Route URLs do NOT change — same paths, same names
- API response shapes do NOT change
- Backward-compat aliases stay until Phase 17 cleanup
- `OperationStatusEnum` stays in `app/Enums/` — shared with TicketSupport
