# Payout module — living design

This document is the source of truth for `Modules/Payout`. Update it whenever a
layer is started, shipped, or deliberately deferred. Do not treat unchecked
items as built.

## 1. Purpose

The platform already moves **numbers** between wallets (holds, releases, debits,
credits). It does **not** move **cash**. Discovery (2026-08-17) confirmed:

- No payment gateway in this codebase can pay out (`PaymentGatewayInterface` is
  inbound-only: `initiate` / `verify`).
- Withdraw approval only decrements internal `wallets.balance`.
- Guarantor end / installment release / admin cancel only adjust pending
  credit/debit. Captured gateway funds are never refunded automatically.
- Order settlement mirrors guarantor end (provider gets wallet balance).
- **Admin always transfers real money outside the system** (bank transfer, etc.).

`PayoutRequest` is the ledger of those real-world outflows: one row per time
money needs to physically leave the platform, linked back to the domain
operation that created the obligation (`operation_type` / `operation_id`, same
morph pattern as `wallet_transactions`).

It unifies, over time:

| Source operation | Why a payout exists |
|---|---|
| `WithdrawRequest` approved | User/provider asked to cash out wallet balance |
| Guarantor end / installment release | Recipient's released wallet credit still needs a bank transfer if they withdraw — *and* any future "pay the person directly" path |
| Guarantor admin cancel after capture | Manual refund of gateway-captured funds (not built) |
| Future order refunds | Manual return of funds to a user |

Layer 1 only wires **withdraw approval**. Other sources are planned, not built.

## 2. Status

### Layer 1 — **built**

- `PayoutRequest` model + module migration
- Create-on-withdraw-approval (purely additive)
- Reserved Spatie permission names `request payouts` / `confirm payouts`
  (granted to `super-admin` only; nothing checks them yet)
- This document

### Layer 2 — **built**

Mandatory maker-checker for **every** payout, with **no amount threshold**:

- `maker_admin_id` (nullable FK → `admins`) set at creation from whichever
  admin's action created the payout (for withdraw-sourced payouts: the admin
  who approved the withdraw, passed through from
  `UpdateWithdrawStatusForDashboardAction`)
- `ConfirmPayoutTransferAction` — requires `confirm payouts` permission
  (controller middleware); rejects when confirming admin equals
  `maker_admin_id` (422); requires `gateway_reference`; sets
  `processed_by_admin_id`, status → `completed`
- `FailPayoutTransferAction` — marks status → `failed` with required
  `failure_reason`; failed payouts can be confirmed later by any eligible
  (different) admin
- Minimal dashboard: list pending/failed payouts, confirm, fail
  (`dashboard.payout-requests.*`)

**Core rule:** the admin who triggered the source operation (the maker) can
never confirm the actual bank transfer — even if they hold `confirm payouts`.
A different admin must record the `gateway_reference`.

### Layer 2.5 — **built**

Permanent manual-confirm audit trail (not a placeholder for automated gateway
payout):

- Every confirm requires **both** `gateway_reference` (free text) **and**
  `proof_image` (required upload) — independently required, same validation
  limits as Chat attachments (`jpeg,jpg,png,gif,webp`, max 5120 KB).
- `PayoutRequest` implements MediaLibrary: collection `transfer_proof` on
  `public` disk, `singleFile()` so a retried confirm after `failed` replaces
  the previous proof (never appends). WebP conversion via `HasWebpImageConversion`.
- Dashboard index supports optional `?status=` filter (`pending`, `failed`,
  `completed`); default (no filter) still lists **pending + failed** only.
  Completed payouts are visible via the Completed tab/filter; each row exposes
  `transfer_proof_url` (prefers WebP when ready) for a "view proof" modal.
- **Automated gateway payout** (e.g. future Adfa Pay outbound driver) remains
  out of scope — when it lands it will be a **separate parallel path**, not a
  replacement for this manual confirm + proof design.

### Not built — planned future layers

| Layer | Intent |
|---|---|
| Audit log | Who created, who confirmed, timestamps, notes, gateway reference |
| Reconciliation report | Outstanding vs completed vs failed; match `gateway_reference` to bank statements |
| Full unified admin dashboard | Rich list/filter UX beyond Layer 2 minimum |
| Guarantor / order sources | Create `PayoutRequest` from end, installment release, cancel-refund, order refunds |
| Automated gateway payout | Out of scope until a driver actually supports outbound transfers; parallel path when Adfa Pay lands — does not replace Layer 2.5 manual confirm |

## 3. Data model

Table: `payout_requests` (UUID PK). Module migration only — **does not** alter
`wallets` or `withdraw_requests`.

| Column | Type | Use |
|---|---|---|
| `id` | UUID | PK |
| `operation_type` / `operation_id` | morph (`char(36)` id, same as `wallet_transactions`) | Source row, e.g. `Modules\Wallet\Models\WithdrawRequest` |
| `recipient_type` / `recipient_id` | morph (bigint; `User` / `Provider`) | Who should receive the cash |
| `amount` | `decimal(12,2)` | Copied from the source operation |
| `status` | `PayoutStatusEnum` | Created as `pending`; transitions via confirm/fail |
| `gateway_reference` | nullable string | Required on confirm; bank/gateway txn id |
| `processed_by_admin_id` | nullable FK → `admins` | Checker who confirmed the transfer |
| `failure_reason` | nullable text | Set on fail; cleared on successful confirm retry |
| `maker_admin_id` | nullable FK → `admins` | Admin whose action created the payout obligation |
| `created_at` / `updated_at` | timestamps | |

Statuses: `pending` → `processing` → `completed` \| `failed`.

Layer 2 confirm/fail: `pending` or `failed` → `completed` (confirm) or `failed`
(fail). `completed` is terminal for confirm/fail.

Relations on `PayoutRequest`: `operation()` morphTo, `recipient()` morphTo,
`processedByAdmin()` belongsTo `Admin`, `makerAdmin()` belongsTo `Admin`.

## 4. Layer 1 wiring (withdraw only)

Existing withdraw behaviour is unchanged:

1. Admin `PUT` dashboard update-status with `approved`
2. Row-lock + must still be `pending`
3. Status / notes / `admin_id` saved
4. `WalletService::finalizeWithdraw(..., approved: true)` — hold released +
   balance debited
5. `WithdrawStatusChangedNotification` as before

**Additive step after (4) succeeds, inside the same DB transaction:**

`PayoutService::createForOperation()` → `CreatePayoutRequestAction` → repository
insert:

- `operation` = the `WithdrawRequest`
- `recipient` = `$withdrawRequest->user` (User or Provider)
- `amount` = withdraw amount
- `status` = `pending`
- `maker_admin_id` = approving admin's id (Layer 2)
- `gateway_reference` / `processed_by_admin_id` / `failure_reason` = null

Reject and cancel do **not** create a `PayoutRequest` (no cash should leave).

Wallet still does not talk to any payment gateway on approve. The new row is
the to-do item for the human transfer that already happened off-system.

## 5. Layer 2 wiring (maker-checker confirm/fail)

Dashboard routes (middleware: `auth:admin`, `confirm payouts`):

- `GET dashboard/payout-requests` — list `pending` + `failed` rows
- `PUT dashboard/payout-requests/{id}/confirm` — body: `gateway_reference`
  (required) and `proof_image` (required file upload)
- `PUT dashboard/payout-requests/{id}/fail` — body: `failure_reason` (required)

Confirm rejects when `auth('admin')->id() === maker_admin_id` with 422 and a
clear message. Confirm rejects when status is already `completed`.

## 6. Module conventions

Follow `Modules/Reviews` / `Modules/Marketplace`, **not** Wallet's older
`RouteServiceProvider` (custom `boot()`, `Routes/V1/wallet.php`).

- `Nwidart\Modules\Support\ModuleServiceProvider`
- `App\Providers\BaseModuleRouteServiceProvider` + `$this->map()`
- Root `composer.json` PSR-4 for `database/factories/` (Geo/Guarantor/Orders
  pattern — do not omit it the way Wallet did)
- Enable in `modules_statuses.json`
- Permissions live in `RolePermissionSeeder` (source of truth)

Layering: Controller → Service → Action → Repository / DTO.

## 7. Changelog

| Date | Layer | Change |
|---|---|---|
| 2026-08-18 | 1 | Scaffold module; create `PayoutRequest` on withdraw approve |
| 2026-08-19 | 2 | Maker-checker: `maker_admin_id`, confirm/fail actions, minimal dashboard |
| 2026-08-19 | 2.5 | Required `transfer_proof` image on confirm; completed payout list + proof viewer |
