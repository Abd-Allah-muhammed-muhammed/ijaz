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

### Layer 1 — **this task (built)**

- `PayoutRequest` model + module migration
- Create-on-withdraw-approval (purely additive)
- Reserved Spatie permission names `request payouts` / `confirm payouts`
  (granted to `super-admin` only; nothing checks them yet)
- This document

### Not built — planned future layers

| Layer | Intent |
|---|---|
| Maker-checker | Two distinct admins: one requests / one confirms. No codebase precedent; new workflow + data. Permissions already reserved. |
| Audit log | Who created, who confirmed, timestamps, notes, gateway reference |
| Reconciliation report | Outstanding vs completed vs failed; match `gateway_reference` to bank statements |
| Unified admin dashboard | List/filter/confirm payouts (Inertia). Layer 1 has **no HTTP surface**. |
| Guarantor / order sources | Create `PayoutRequest` from end, installment release, cancel-refund, order refunds |
| Automated gateway payout | Out of scope until a driver actually supports outbound transfers |

## 3. Data model

Table: `payout_requests` (UUID PK). Module migration only — **does not** alter
`wallets` or `withdraw_requests`.

| Column | Type | Layer 1 use |
|---|---|---|
| `id` | UUID | PK |
| `operation_type` / `operation_id` | morph (`char(36)` id, same as `wallet_transactions`) | Source row, e.g. `Modules\Wallet\Models\WithdrawRequest` |
| `recipient_type` / `recipient_id` | morph (bigint; `User` / `Provider`) | Who should receive the cash |
| `amount` | `decimal(12,2)` | Copied from the source operation |
| `status` | `PayoutStatusEnum` | Created as `pending` |
| `gateway_reference` | nullable string | Filled when admin confirms the external transfer (later layer) |
| `processed_by_admin_id` | nullable FK → `admins` | Confirmer, not the withdraw approver (later layer) |
| `failure_reason` | nullable text | Later layer |
| `created_at` / `updated_at` | timestamps | |

Statuses: `pending` → `processing` → `completed` \| `failed`.

Layer 1 only **inserts** `pending` rows. No status transitions yet.

Relations on `PayoutRequest`: `operation()` morphTo, `recipient()` morphTo,
`processedByAdmin()` belongsTo `Admin`.

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
- `gateway_reference` / `processed_by_admin_id` / `failure_reason` = null

Reject and cancel do **not** create a `PayoutRequest` (no cash should leave).

Wallet still does not talk to any payment gateway on approve. The new row is
the to-do item for the human transfer that already happened off-system.

## 5. Module conventions

Follow `Modules/Reviews` / `Modules/Marketplace`, **not** Wallet's older
`RouteServiceProvider` (custom `boot()`, `Routes/V1/wallet.php`).

- `Nwidart\Modules\Support\ModuleServiceProvider`
- `App\Providers\BaseModuleRouteServiceProvider` + `$this->map()`
- Root `composer.json` PSR-4 for `database/factories/` (Geo/Guarantor/Orders
  pattern — do not omit it the way Wallet did)
- Enable in `modules_statuses.json`
- Permissions live in `RolePermissionSeeder` (source of truth)

Layering: Controller (later) → Service → Action → Repository / DTO.

## 6. Changelog

| Date | Layer | Change |
|---|---|---|
| 2026-08-18 | 1 | Scaffold module; create `PayoutRequest` on withdraw approve |
