# Deferred Mobile-Breaking Changes

> **These items were deliberately deferred during the pre-MVP cleanup pass (target ship: 15/8) to avoid any mobile-breaking changes. Do not fix any of these without first confirming with the mobile app team and planning a proper versioned rollout.**

| Field | Value |
|-------|--------|
| Branch context | `refactor/pass-3-cleanup` |
| Created | 2026-07-20 |
| Revisit after | MVP ship (**15/8**) |
| Related safe fixes already landed | `Api/Api` namespace rename, `HasJobs` MorphMany, wallet response construction without spread, `phone_verified_at` side-effect (response still `success: false`) |

---

## Table of contents

1. [Summary](#summary)
2. [Item 1 — `last_massage_at` deprecated typo key](#item-1--last_massage_at-deprecated-typo-key)
3. [Item 2 — Pagination response shape fragmentation](#item-2--pagination-response-shape-fragmentation)
4. [Item 3 — Wallet `addBalance` leaking `PaymentInitResult` internals](#item-3--wallet-addbalance-leaking-paymentinitresult-internals)
5. [Item 4 — Phone OTP verify returning `success: false` always](#item-4--phone-otp-verify-returning-success-false-always)
6. [Item 5 — `PropertiyCategory` / `propertiy_categories` systemic naming typo](#item-5--propertiycategory--propertiy_categories-systemic-naming-typo)
7. [Item 6 — `markPhoneAsVerified()` response semantics](#item-6--markphoneasverified-response-semantics)

---

## Summary

| # | Item | Risk if fixed today | Target |
|---|------|---------------------|--------|
| 1 | `last_massage_at` | Breaks clients reading typo key | Post-MVP, phased |
| 2 | Pagination shapes | Breaks all list-consuming screens | Post-MVP, API version |
| 3 | Wallet response leak | Breaks clients depending on exposed fields | Post-MVP, confirm usage first |
| 4 | Phone OTP `success: false` | Currently misleading but not breaking (already `false` today) | Post-MVP, coordinate UI |
| 5 | `PropertiyCategory` rename | Breaks any hardcoded references + requires data migration | Post-MVP, careful migration |
| 6 | Phone OTP semantics | Same as Item 4 (consolidated) | See Item 4 |

---

## Item 1 — `last_massage_at` deprecated typo key

### What's wrong

Conversation payloads emit a misspelled duplicate timestamp field `last_massage_at` alongside the correct `last_message_at`. Both keys carry the same humanized relative time string. The DB column and Eloquent attribute are already correct (`last_message_at`); only the serialized JSON typo remains.

### Current behavior

**HTTP resource** (`Modules/Chat/Http/Resources/ConversationResource.php`):

```php
'last_message_at' => $this->last_message_at?->shortAbsoluteDiffForHumans(),
'last_massage_at' => $this->last_message_at?->shortAbsoluteDiffForHumans(), // @deprecated typo — use last_message_at
```

**Realtime event** (`Modules/Chat/Infrastructure/Events/ChatUpdatedEvent.php`):

```php
'last_message_at' => $this->chat->last_message_at?->shortAbsoluteDiffForHumans(),
'last_massage_at' => $this->chat->last_message_at?->shortAbsoluteDiffForHumans(), // @deprecated typo — use last_message_at
```

Example payload fragment:

```json
{
  "last_message_at": "2 hours ago",
  "last_massage_at": "2 hours ago"
}
```

**Mobile app version(s) still reading the typo key:** **Unknown** — confirm with the mobile team before removing. Do not assume zero usage because the correct key already exists; older builds may still bind to the typo.

### Why it's deferred

Removing `last_massage_at` today breaks any install that still reads that key (chat list timestamps go blank/null, possible parse crashes depending on client null-handling). Emitting only the typo would break clients that already migrated to `last_message_at`.

### Proposed fix

1. Confirm with mobile which store builds still read `last_massage_at`.
2. Once usage is confirmed zero (or after a forced minimum app version), remove the typo key from both `ConversationResource` and `ChatUpdatedEvent` in the same release.
3. Keep `last_message_at` as the sole field forever after.

### Suggested approach for a safe rollout

1. **Metric / log window (additive):** keep both keys; add a temporary server log or analytics flag when a client header/user-agent known to use the typo is detected (if identifiable), or ask mobile to ship a build that reports which key it uses.
2. **Deprecation period:** continue emitting both for N months after MVP (or until the oldest supported app version no longer references the typo).
3. **Removal gate:** remove only after mobile signs off that minimum supported version uses `last_message_at` only — **or** introduce the removal only on `/api/v2/` / `Accept: application/vnd.ijaz.v2+json` while v1 keeps both.
4. Prefer **confirm with mobile team before removing** over guessing from backend code alone.

---

## Item 2 — Pagination response shape fragmentation

### What's wrong

List endpoints do not share one pagination JSON contract. Clients must special-case chat vs catalog/jobs vs message threads. Unifying the shape is desirable for DX and client codegen, but any single target shape will break at least one family of screens.

### Current behavior

#### A — Flat meta (`App\Http\Resources\Api\BaseCollection`)

Used by e.g. jobs, car advisements (`CarAdvisementCollection` extends `BaseCollection`).

```json
{
  "items": [ /* ... */ ],
  "total": 42,
  "count": 15,
  "per_page": 15,
  "current_page": 1,
  "last_page": 3,
  "has_more_pages": true
}
```

```php
return [
    'items' => $this->collection,
    'total' => $this->total(),
    'count' => $this->count(),
    'per_page' => $this->perPage(),
    'current_page' => $this->currentPage(),
    'last_page' => $this->lastPage(),
    'has_more_pages' => $this->hasMorePages(),
];
```

#### B — Nested `paginate` (`Modules\Chat\Http\Resources\ConversationCollection`)

```json
{
  "items": [ /* ... */ ],
  "paginate": {
    "total": 10,
    "count": 10,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

#### C — Nested `paginate` + page URLs (`Modules\Chat\Http\Resources\ConversationMessageCollection`)

```json
{
  "items": [ /* ... */ ],
  "paginate": {
    "total": 100,
    "count": 20,
    "per_page": 20,
    "next_page_url": "https://example.test/api/v1/chats/…?page=2",
    "prev_page_url": null,
    "current_page": 1,
    "last_page": 5,
    "has_more_pages": true
  }
}
```

(All of the above sit under the usual API envelope `success` / `data` / etc.)

### Why it's deferred

Changing B→A or A→B moves pagination fields (e.g. `data.total` vs `data.paginate.total`). Infinite-scroll / “load more” logic on every affected screen breaks unless every client build is updated in lockstep. Message lists also lose or gain `next_page_url` / `prev_page_url`.

### Proposed fix

Pick one **target** shape (recommendation: nested `paginate` object matching `ConversationMessageCollection`, including optional nullable `next_page_url` / `prev_page_url`, so chat messages keep URL helpers and other lists can send `null`). Refactor all collections to share a single base class that emits that shape.

Internal DRY **without** changing output was considered SAFE-NOW during Pass 3; this deferred item is specifically about **unifying the public JSON**.

### Suggested approach for a safe rollout

- **API version bump** (`/api/v2/…` or `Accept` version header): v1 keeps legacy shapes; v2 is unified only.
- Or a **long dual-emit window**: temporarily include both flat keys *and* `paginate` (additive), then drop flat keys after mobile migrates — noisier payloads, but no hard cut for old installs.
- Do **not** flip production v1 shapes in place without a coordinated app release + minimum version policy.

---

## Item 3 — Wallet `addBalance` leaking `PaymentInitResult` internals

### What's wrong

Online top-up success responses expose payment-driver DTO fields at the top level of the wallet payload (`status`, `driver`, `url`, `payable`, `transaction_id`, `message`) plus nested top-up `data`. That mirrors `PaymentInitResult::toArray()` historically (Pass 3 only removed the PHP spread; **keys/values unchanged**).

### Current behavior

`Modules/Wallet/Http/Controllers/V1/WalletController.php` (online success path):

```php
return $this->successResponse([
    'status' => $paymentResult->status,
    'driver' => $paymentResult->driver,
    'url' => $paymentResult->url,
    'payable' => $paymentResult->payable,
    'transaction_id' => $paymentResult->transactionId,
    'message' => $paymentResult->message,
    'data' => TopUpResource::make($topUpRequest),
]);
```

Illustrative envelope `data` object:

```json
{
  "status": "success",
  "driver": "testing",
  "url": "https://…/payment/testing/checkout/…",
  "payable": true,
  "transaction_id": "…",
  "message": null,
  "data": {
    "id": 1,
    "amount": 100,
    "status": "pending",
    "payment_method": "online"
  }
}
```

Offline path similarly exposes `status`, `driver`, `url`, `payable`, `transaction_id`, `message`, `data` (with `driver: "offline"`, empty URL, etc.).

### Why it's deferred

Any removal/rename of top-level payment fields breaks clients that open `url`, show `driver`, or gate UI on `payable` / `transaction_id`. We have **not** confirmed which fields mobile consumes; stripping “implementation detail” without that audit is unsafe.

### Proposed fix

Wallet-owned contract, for example:

```json
{
  "top_up": { /* TopUpResource */ },
  "checkout": {
    "url": "…",
    "transaction_id": "…",
    "driver": "…"
  }
}
```

Or keep only what product needs (e.g. `url` + `top_up`) and drop `payable` / raw gateway `status` if unused. Exact shape **requires mobile confirmation**.

### Suggested approach for a safe rollout

1. Mobile inventory: which keys of today’s response are read in production builds.
2. Additive phase: introduce namespaced `checkout` / `top_up` **while keeping** legacy top-level keys.
3. Deprecate legacy keys in release notes; remove only on v2 or after min app version.
4. Until then, keep byte-compatible with current tests (`wallet addBalance response shape is unchanged`).

---

## Item 4 — Phone OTP verify returning `success: false` always

### What's wrong

`POST /api/v1/otp/verify` with `type=phone` persists `phone_verified_at` (Pass 3 side-effect fix) but still returns the API envelope with `success: false`. Server-side verification succeeded; the client is told it failed. Misleading for UX and for any client logic that treats `success` as source of truth.

### Current behavior

`app/Actions/Auth/User/VerifyOtpAction.php`:

```php
case 'phone':
    // Side-effect now actually persists (previously a no-op stub), but the
    // response contract is deliberately UNCHANGED (still success: false) to
    // avoid a mobile-breaking change. Making this endpoint return success:true
    // is a deferred product decision — see docs/DEFERRED_MOBILE_BREAKING_CHANGES.md
    $model->markPhoneAsVerified();

    return new OtpVerifyResult(success: false);
```

Controller maps that to `makeResponse(false, [], '', [], '')` → roughly:

```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "",
  "token": ""
}
```

Contrast with `type=email` / `type=login`, which return `success: true` (and user resource / token as applicable).

Regression lock: `tests/Feature/Api/V1/OtpVerifyPhoneResponseTest.php` asserts `success` stays `false`.

### Why it's deferred

Flipping to `success: true` (and likely attaching a `UserResource`) changes the contract. Clients that treat `success === false` as “show error / stay on OTP screen” would suddenly see success UI — or, if they ignore `success` and always advance, behavior might already be weird today. **Must confirm current mobile UI handling** before changing.

### Proposed fix

Align with email/login:

```php
case 'phone':
    $model->markPhoneAsVerified();

    return new OtpVerifyResult(
        success: true,
        data: $this->getUserResource($model->fresh()),
    );
```

(Exact resource/fields to confirm with product.)

### Suggested approach for a safe rollout

1. Ask mobile: on phone OTP verify today, does a `success: false` body show an error toast, silently continue, or branch on something else?
2. If the app already ignores `success` for phone, a coordinated release can flip backend + tighten client checks together.
3. Prefer shipping behind app-version gate or `/api/v2/otp/verify` so old installs keep the historical `false` envelope.
4. Update/remove the regression test only when the new contract is intentional.

---

## Item 5 — `PropertiyCategory` / `propertiy_categories` systemic naming typo

### What's wrong

“Property” was misspelled as **Propertiy** across the Catalog/Classifieds stack: PHP class names, table names, FK columns, factories, and seeders. Unlike Items 1–4, this is not only a JSON key issue — it is a **schema + codebase** rename. Public API resources often already say `PropertyCategory` in class names while Eloquent/tables still use the typo.

### Current behavior

**Tables / columns (from migrations):**

- `propertiy_categories`
- `propertiy_category_translations`
- FK: `propertiy_category_id` (on translations; also referenced from property advisements)

**Model:** `Modules\Catalog\Models\PropertiyCategory` (and `PropertiyCategoryTranslation`).

### Full file list (19 PHP files referencing the typo — Pass 3 scope)

1. `Modules/Catalog/Models/PropertiyCategory.php`
2. `Modules/Catalog/Models/PropertiyCategoryTranslation.php`
3. `Modules/Catalog/database/factories/PropertiyCategoryFactory.php`
4. `Modules/Catalog/database/migrations/2026_02_18_214050_create_propertiy_categories_table.php`
5. `Modules/Catalog/database/seeders/PropertyCategoriesSeeder.php`
6. `Modules/Catalog/Repositories/PropertyCategoryRepository.php`
7. `Modules/Catalog/Http/Controllers/Dashboard/PropertyCategoryController.php`
8. `Modules/Catalog/Http/Controllers/General/CatalogSelectController.php`
9. `Modules/Catalog/Http/Requests/Dashboard/PropertyCategoryRequest.php`
10. `Modules/Catalog/Http/Resources/Api/PropertyCategoryResource.php`
11. `Modules/Catalog/Http/Resources/Dashboard/PropertyCategoryResource.php`
12. `Modules/Catalog/tests/Feature/PropertyCategoryControllerTest.php`
13. `Modules/Classifieds/Models/PropertyAdvisement.php`
14. `Modules/Classifieds/Http/Requests/Api/PropertyAdvisementRequest.php`
15. `Modules/Classifieds/Http/Controllers/Dashboard/PropertyAdvisementController.php`
16. `Modules/Classifieds/database/factories/PropertyAdvisementFactory.php`
17. `Modules/Classifieds/database/seeders/PropertyAdvisementsSeeder.php`
18. `Modules/Classifieds/database/migrations/2026_03_06_031831_create_property_advisements_table.php`
19. `Modules/Classifieds/tests/Feature/PropertyAdvisementControllerTest.php`

### Why it's deferred

1. Renaming tables/columns without a careful migration loses or breaks FKs and production data.
2. All 19 files (plus any generated Wayfinder/IDE aliases) must move together.
3. Mobile (or caching layers) might hardcode the literal string `propertiy_categories` in error messages, offline caches, or analytics — **confirm before rename**. Even if JSON field names already say “property”, schema errors or raw SQL leaks could surface the typo string.

### Proposed fix

Rename to correct English spelling end-to-end:

- Tables: `property_categories`, `property_category_translations`
- Column: `property_category_id`
- Classes: `PropertyCategory`, `PropertyCategoryTranslation`, matching factory

### Suggested approach for a safe rollout

1. **Additive migration (preferred for zero downtime):**
   - Create correctly named tables (or `rename` in a maintenance window if acceptable).
   - Backfill; dual-write or read-new/write-both during transition if needed.
   - Swap app code to new names; drop old tables after verification.
2. **Single maintenance window rename** (`Schema::rename` + FK rebuild) only if downtime is approved and mobile has no hardcoded typo strings.
3. Confirm mobile does **not** key caches or display logic on `propertiy_*` literals.
4. Treat as a dedicated post-MVP epic (schema + code + tests), not a drive-by cleanup PR.

---

## Item 6 — `HasOTPs::markPhoneAsVerified()` response semantics

**Consolidated into [Item 4](#item-4--phone-otp-verify-returning-success-false-always).**

Pass 3 already implemented the **side-effect** (`phone_verified_at` column + `markPhoneAsVerified()`). What remains deferred is solely the **response contract** (`success: true` + resource). Do not re-open the side-effect as a separate mobile-breaking item.

| Layer | Status |
|-------|--------|
| Persistence (`phone_verified_at`) | Done (response-identical) |
| HTTP `success` / payload for `type=phone` | Deferred — Item 4 |

---

## How to use this doc

- Pre-MVP: **do not** land breaking fixes from this list on v1 without mobile + versioning plan.
- Post-15/8: pick items with mobile, attach an ADR or ticket per item, and prefer additive → deprecate → remove (or v2-only).
- When an item is fixed and rolled out, move it to a “Completed” section here or delete the section and link the PR.
