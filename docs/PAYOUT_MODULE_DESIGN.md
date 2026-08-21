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
Submit/review lives entirely on `PayoutRequest` (source-agnostic) — never
duplicated inside Wallet, Guarantor, or future source modules.

## 2. Status

### Layer 1 — **built**

- `PayoutRequest` model + module migration
- Create-on-withdraw-approval (purely additive)
- Reserved Spatie permission names `request payouts` / `confirm payouts`
- This document

### Layer 2 — **built** (superseded in part by 2.6)

Originally: one-shot confirm with `maker_admin_id` blocking the confirming
admin. Layer 2.6 splits that into submit + review (below). `maker_admin_id`
remains as audit metadata of who created the payout obligation.

### Layer 2.5 — **built**

Permanent manual transfer-proof audit trail (not a placeholder for automated
gateway payout):

- Submit requires **both** `gateway_reference` (free text) **and**
  `proof_image` (required upload) — same validation limits as Chat attachments
  (`jpeg,jpg,png,gif,webp`, max 5120 KB).
- `PayoutRequest` implements MediaLibrary: collection `transfer_proof` on
  `public` disk, `singleFile()` so a re-submit after `failed` replaces the
  previous proof (never appends). WebP conversion via `HasWebpImageConversion`.
- **Automated gateway payout** (e.g. future Adfa Pay outbound driver) remains
  out of scope — when it lands it will be a **separate parallel path**, not a
  replacement for this manual submit + proof design.

### Layer 2.6 — **built**

Splits one-shot "Confirm" into two roles (four-eyes / maker-checker practice).
Applies to **every** `PayoutRequest` regardless of source:

1. **Submit** (`request payouts`) — any eligible admin, **including** the
   original `maker_admin_id`, uploads `gateway_reference` + `proof_image`.
   Transitions `pending` \| `failed` → `submitted`. Records
   `submitted_by_admin_id`. Clears `failure_reason` on a fresh attempt.
2. **Review-approve** (`confirm payouts`) — an admin **different from**
   `submitted_by_admin_id`. Transitions `submitted` → `completed`. Sets
   `processed_by_admin_id`, clears `failure_reason`. Does **not** re-upload
   proof (already set at submit).
3. **Review-reject** (`confirm payouts`) — same identity guard as approve.
   Transitions `submitted` → `failed` with required `failure_reason`.
4. **Direct fail** (`confirm payouts`) — separate path: `pending` → `failed`
   with required `failure_reason`, **no** submitter/maker restriction. Used
   when a payout can never be transferred (e.g. bad bank details) before any
   evidence is submitted.

**What review verifies:** legitimacy of the submitted evidence (amount /
reference consistency, receipt looks genuine). Review does **not** wait for
recipient confirmation of receipt. A post-completion dispute / "disputed" flag
is a separate future flow, out of scope here.

**`maker_admin_id`:** audit-only metadata (who approved the source operation).
It no longer blocks submit or review. The enforced separation is
`submitted_by_admin_id` vs the reviewing admin.

Permissions: `request payouts` granted to `super-admin` and `finance`;
`confirm payouts` remains `super-admin`-only for now.

Dashboard: tabs for active queue (pending + submitted + failed), pending,
submitted, failed, completed. Sidebar visible for either payout permission.

**Provider-facing status:** Surfaced on **both**:

1. Mobile `GET /api/v1/wallet/transaction` (withdraw-related ledger rows)
2. Provider web `provider.withdraw-requests.*` Inertia list/show (`WithdrawResource`)

Both use the same shared `PayoutStatusEnum::toProviderStatus()` mapping
and `WithdrawRequest::payoutRequest()` morphOne — one source of truth, two
surfaces:

| Internal | Provider `value` |
|---|---|
| `pending` / `submitted` (/ unused `processing`) | `in_progress` |
| `completed` | `transferred` |
| `failed` | `delayed` |

Shape matches Guarantor API status objects: `{ value, label, color }` with
labels under `payout.transfer_status.*`. Never exposes `maker_admin_id`,
`submitted_by_admin_id`, `gateway_reference`, proof images, or
`failure_reason`. Non-withdraw / no-payout cases get `transfer_status: null`.
Eager-loaded (`morphWith` on mobile transaction list; `with('payoutRequest')`
on provider/admin withdraw lists) to avoid N+1.

### Not built — planned future layers

| Layer | Intent |
|---|---|
| Audit log | Who created, who submitted, who reviewed, timestamps, notes |
| Reconciliation report | Outstanding vs completed vs failed; match `gateway_reference` to bank statements |
| Full unified admin dashboard | Rich list/filter UX beyond Layer 2 minimum |
| Guarantor / order sources | Create `PayoutRequest` from end, installment release, cancel-refund, order refunds |
| Automated gateway payout | Out of scope until a driver actually supports outbound transfers; parallel path when Adfa Pay lands — does not replace manual submit + proof |
| Post-completion dispute | Recipient claims non-receipt after `completed` — investigation flow, not a blocker on this state machine |

## 3. Data model

Table: `payout_requests` (UUID PK). Module migration only — **does not** alter
`wallets` or `withdraw_requests`.

| Column | Type | Use |
|---|---|---|
| `id` | UUID | PK |
| `operation_type` / `operation_id` | morph (`char(36)` id, same as `wallet_transactions`) | Source row, e.g. `Modules\Wallet\Models\WithdrawRequest` |
| `recipient_type` / `recipient_id` | morph (bigint; `User` / `Provider`) | Who should receive the cash |
| `amount` | `decimal(12,2)` | Copied from the source operation |
| `status` | `PayoutStatusEnum` (string column + PHP cast) | See transitions below |
| `gateway_reference` | nullable string | Required on submit; bank/gateway txn id |
| `processed_by_admin_id` | nullable FK → `admins` | Reviewer who approved |
| `failure_reason` | nullable text | Set on direct-fail or review-reject; cleared on submit / approve |
| `maker_admin_id` | nullable FK → `admins` | Audit: admin whose action created the payout obligation |
| `submitted_by_admin_id` | nullable FK → `admins` | Admin who submitted transfer proof (enforced at review) |
| `created_at` / `updated_at` | timestamps | |

Statuses: `pending` → `submitted` → `completed` \| `failed`. Also:
`pending` → `failed` (direct fail). `failed` → `submitted` (re-submit).
`processing` exists on the enum but is unused (do not repurpose).

Relations on `PayoutRequest`: `operation()` morphTo, `recipient()` morphTo,
`processedByAdmin()` / `makerAdmin()` / `submittedByAdmin()` belongsTo `Admin`.

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
- `maker_admin_id` = approving admin's id
- `gateway_reference` / `processed_by_admin_id` / `submitted_by_admin_id` /
  `failure_reason` = null

Reject and cancel do **not** create a `PayoutRequest` (no cash should leave).

## 5. Layer 2.6 wiring (submit / review / direct-fail)

Dashboard routes (`auth:admin`):

- `GET dashboard/payout-requests` — middleware: `request payouts` **or**
  `confirm payouts`; default list pending + submitted + failed
- `PUT …/submit` — `request payouts`; body: `gateway_reference` + `proof_image`
- `PUT …/confirm` — `confirm payouts`; review-approve (no body)
- `PUT …/reject` — `confirm payouts`; body: `failure_reason`
- `PUT …/fail` — `confirm payouts`; direct-fail from pending; body: `failure_reason`

Review rejects when `auth('admin')->id() === submitted_by_admin_id`.

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
| 2026-08-20 | 2.6 | Split submit vs review; `submitted` status + `submitted_by_admin_id`; maker audit-only; direct-fail vs review-reject |
| 2026-08-22 | 2.6 | Provider `transfer_status` on mobile wallet transaction history for withdraw ops |
| 2026-08-22 | 2.6 | Same `transfer_status` on provider web withdraw-requests list (shared mapping) |
