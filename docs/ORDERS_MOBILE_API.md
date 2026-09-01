# Orders — Mobile API reference

Standalone contract for a Flutter (or other mobile) client. Every path, field name, enum string, validation rule, and error message below is taken from the **current** API routes, request validation, JSON resources, authorization rules, and notification payloads. Where the code is ambiguous, this document says so instead of guessing.

**Base URL prefix:** `/api/v1`

**Auth (two surfaces):**

| Surface | Middleware | Token | Routes |
|---|---|---|---|
| **User (client) orders API** | `auth:user-api` + `user.active` | User Sanctum token (`Authorization: Bearer …`) | `/api/v1/user/orders/*` |
| **Shared Sanctum** | `auth:sanctum` + `user.active` | User **or** Provider Sanctum token (same pattern as chat, guarantor, wallet) | `/api/v1/orders/{order}/dispute`, `/api/v1/chats/orders/*` |

See `docs/mobile/AUTH_FLOW.md` for token issuance. Inactive / banned Users receive `403` and the token is revoked.

**Locale:** send `Accept-Language` (`en`, `ar`, `ur`, `hi`, …). Status labels, validation messages, and notification title/body strings follow this locale.

**Rate limit:** API routes are throttled at **60 requests per minute**.

**IDs:** order, offer, conversation, message, and media `id` values are **UUIDs**.

**Provider offer/end/cancel JSON routes:** There are **no** `/api/v1` JSON endpoints for provider submit/edit/delete offer, provider end, or provider cancel today. Those mutations run through the **Provider web dashboard** (session `auth:provider`, Inertia redirects). Section [2.13](#213-provider-order-mutations-no-apiv1-json-routes-yet) documents the **business contract** from the underlying Actions so Provider mobile can align when JSON routes ship. Chat and dispute already use shared Sanctum.

---

## Table of contents

1. [Overview](#1-overview)
2. [Common envelopes](#common-envelopes)
3. [Shared JSON objects](#shared-json-objects)
4. [Full endpoint reference](#2-full-endpoint-reference)
5. [Status reference](#3-status-reference)
6. [Lifecycle (UI)](#4-the-full-lifecycle-step-by-step)
7. [Chat](#5-chat)
8. [Notifications](#6-notifications-the-mobile-app-should-expect)
9. [Dispute feature (complete reference)](#7-dispute-feature--complete-reference)
10. [Known current limitations](#8-known-current-limitations)

---

## 1. Overview

An **order** is a client-posted job request. **Providers** submit priced offers; the **client (User)** accepts one offer, pays, work runs under wallet escrow, then either party may end, cancel (post-payment), or open a **dispute**. Admin resolves disputes from the Dashboard only — mobile **observes** outcomes.

| Role | Who | What they do |
|---|---|---|
| **Client (User)** | Order owner | Creates/edits/deletes the order while `new`, reviews offers, accepts/rejects/cancels offers, pays, ends with review, cancels in `in_progress`, opens disputes. |
| **Provider** | Service provider | Submits/edits/deletes **one** pending offer while order is `new`, edits accepted-offer price (with asymmetric rules after accept), ends order from `in_progress`, cancels from `in_progress`, opens disputes. |
| **Admin** | Dashboard only | Resolves disputes (4 paths). No mobile resolve endpoint. |

### Money in escrow

After successful payment, the user's wallet gets a **pending debit** and the provider's wallet gets **pending credit** (plus provider fee hold). End/settle releases provider credit; cancel/dispute resolutions reverse holds **internally** — see [Known limitations](#8-known-current-limitations) for gateway refunds.

### Fees

Platform fees are calculated server-side (`CalculateOrderFeesAction`) when an offer is accepted and again when payment completes if amounts are validated. The client reads **`user_total`**, **`user_fees`**, **`provider_total`**, **`provider_fees`**, and **`price`** from `OrderResource` — do not hardcode fee amounts.

---

## Common envelopes

Success and most domain/validation errors use this shape (empty `errors` is usually `{}`; some domain errors use `[]` — see below):

```json
{
  "success": true,
  "data": {},
  "errors": {},
  "message": "",
  "token": ""
}
```

Some User order endpoints return **`successMessageResponse`** (message only, no `data` body) on accept/reject/cancel/end — e.g. `{ "success": true, "message": "Data saved successfully", … }` with localized `message`.

### HTTP 401 — missing / invalid token

```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "Unauthenticated.",
  "token": ""
}
```

### HTTP 403 — policy / authorization

Authenticated but not allowed. Example policy denial on dispute:

```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "This action is unauthorized.",
  "token": ""
}
```

Inactive User middleware returns `403` with a status-specific rejection message.

### HTTP 404 — unknown UUID or wrong owner

Non-owners hitting User order routes get **`404`** (ownership is enforced via `abort(404)`, not `403`). Example:

```json
{
  "success": false,
  "message": "Resource not found",
  "data": [],
  "errors": []
}
```

### HTTP 422 — validation

```json
{
  "success": false,
  "data": [],
  "errors": {
    "reason": ["The reason field is required."]
  },
  "message": "Validation Failed",
  "token": ""
}
```

### HTTP 422 — domain / business rule (`OrdersException`)

```json
{
  "success": false,
  "message": "This order already has an accepted offer.",
  "data": [],
  "errors": []
}
```

`message` is the translated string for the exception key (e.g. `order_already_has_accepted_offer`). `errors` is empty — not a field map.

### HTTP 400 — some offer update errors

`UpdateOfferStatusAction` throws `OrdersException` with HTTP **400** for invalid offer state (`you can not update this offer`). Payment initiation uses **400** for `you can not pay for this order`.

---

## Shared JSON objects

### Status / type object

Enums serialize as:

```json
{ "value": "in_progress", "label": "In Progress", "color": "info" }
```

`label` is translated via the enum's `value` key in `lang/*.json` (e.g. `"in_progress": "In Progress"`). **`color` is a Bootstrap semantic token** (`primary`, `info`, `warning`, `danger`, `success`, `dark`) — **not** a hex string (unlike Guarantor).

### Order (API resource)

Always-present keys on show/create/edit responses:

| Field | Type | Notes |
|---|---|---|
| `id` | UUID | |
| `title`, `description` | string | |
| `expected_time` | string \| null | |
| `budget_start`, `budget_end` | number | Client budget range |
| `price` | decimal string \| null | Set when offer accepted; recalculated on pay |
| `status` | object | See status table |
| `user_fees`, `provider_fees` | decimal string \| null | |
| `user_total`, `provider_total` | decimal string \| null | Pay uses `user_total` |
| `accepted_offer_id` | UUID \| null | **Must match** offer id passed to pay |
| `cancellation_reason` | string \| null | |
| `cancelled_at` | ISO-8601 \| null | |
| `dispute_resolution` | object \| null | Percentage-split snapshot only; see [§7](#7-dispute-feature--complete-reference) |
| `created_at` | ISO-8601 | |

Loaded when relation eager-loaded (may be **omitted** otherwise):

| Field | When |
|---|---|
| `category`, `city`, `region`, `skills` | create, show, edit |
| `user`, `provider` | list, show |
| `offers`, `offers_count` | show (and list where loaded) |
| `accepted_offer` | show |
| `media`, `media_count` | create, show, edit |
| `histories`, `histories_count` | show when repository loads histories |

### Offer

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000010",
  "provider": { "id": 5, "name": "…", "type": "provider", "image": "…" },
  "price": "250.00",
  "description": "I can complete this in 3 days",
  "status": { "value": "pending", "label": "Pending", "color": "primary" },
  "created_at": "2026-09-01T10:00:00+00:00"
}
```

### Order (full resource — show / create / edit / dispute open)

`GET /api/v1/user/orders/{order}` returns this shape inside `data`. **`histories` is not loaded on show today** — only `POST /api/v1/orders/{order}/dispute` eager-loads `histories` on the response. Rely on notifications or poll status fields until show loads histories consistently.

```json
{
  "success": true,
  "data": {
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "title": "Fix kitchen plumbing",
    "description": "Leaking sink and blocked drain",
    "expected_time": "3 days",
    "budget_start": 200,
    "budget_end": 500,
    "price": "250.00",
    "status": { "value": "offer_provided", "label": "Offer Provided", "color": "info" },
    "user_fees": "12.50",
    "provider_fees": "25.00",
    "user_total": "262.50",
    "provider_total": "225.00",
    "accepted_offer_id": "9f3c2a1b-0000-4000-8000-000000000010",
    "cancellation_reason": null,
    "cancelled_at": null,
    "dispute_resolution": null,
    "created_at": "2026-09-01T10:00:00+00:00",
    "category": { "id": 3, "title": "Plumbing" },
    "city": { "id": 1, "title": "Riyadh", "region_id": 1 },
    "region": { "id": 1, "title": "Riyadh" },
    "user": { "id": 10, "name": "Ahmed", "type": "user", "image": null },
    "provider": { "id": 5, "name": "Pro Services", "type": "provider", "image": null },
    "offers": [
      {
        "id": "9f3c2a1b-0000-4000-8000-000000000010",
        "provider": { "id": 5, "name": "Pro Services", "type": "provider", "image": null },
        "price": "250.00",
        "description": "Complete repair in 2 days",
        "status": { "value": "accepted", "label": "Accepted", "color": "success" },
        "created_at": "2026-09-01T11:00:00+00:00"
      }
    ],
    "offers_count": 1,
    "accepted_offer": { "id": "9f3c2a1b-0000-4000-8000-000000000010", "price": "250.00", "status": { "value": "accepted", "label": "Accepted", "color": "success" } },
    "media": [],
    "media_count": 0,
    "skills": [{ "id": 7, "title": "Plumbing" }],
    "skills_count": 1
  },
  "errors": {},
  "message": "",
  "token": ""
}
```

When `status.value` is `settled` after admin percentage-split resolution:

```json
"dispute_resolution": {
  "user_percentage": 60,
  "provider_percentage": 40,
  "user_amount": "157.50",
  "provider_amount": "90.00"
}
```

`dispute_resolution` is **`null`** for all other statuses/outcomes.

### Status history row (`histories[]` — dispute open response only today)

When loaded (dispute `POST` success), each row includes `from_status`, `to_status` (status objects or null), `reason` (`{ value, label } | null`), `notes`, `actor` (`id`, `name`, `type` class basename), `created_at`.

For dispute **open**, `reason.value` is the **verbatim user string** from the request body; `reason.label` equals `reason.value`.

For admin **resolution**, `reason.value` uses machine codes (translated in `reason.label`):

| `reason.value` (exact) | Meaning |
|---|---|
| `dispute_resolved_full_user` | Full to client |
| `dispute_resolved_full_provider` | Full to provider |
| `dispute_escalated_to_court` | Escalated |
| `dispute_resolved_percentage_split:60/40` | Percentage split (percentages in string) |

### List pagination

```json
{
  "items": [ /* Order objects */ ],
  "total": 25,
  "count": 10,
  "per_page": 10,
  "current_page": 1,
  "last_page": 3,
  "has_more_pages": true
}
```

---

## 2. Full endpoint reference

### 2.1 List my orders (User)

`GET /api/v1/user/orders`

**Auth:** `user-api`.

**Query:** `per_page` (default `10`).

**Success `200`:** paginated `OrderResource` items for the authenticated User only.

**Errors:** `401`, inactive `403`.

---

### 2.2 Create order (User)

`POST /api/v1/user/orders`

**Auth:** `user-api`.

**Content-Type:** `multipart/form-data` if uploading files.

**Body:**

| Field | Required | Rules |
|---|---|---|
| `title` | yes | string, max 255 |
| `description` | no | string, max 1000 |
| `budget_start`, `budget_end` | yes | numeric, min 0 |
| `category_id` | yes | exists:categories |
| `provider_id` | no | exists:providers — direct assignment |
| `expected_time` | no | string, max 191 |
| `skills` | sometimes | array min 1; `skills.*` exists:skills. May be JSON string (auto-decoded) |
| `files` | no | array; each image jpeg/png/jpg/gif/svg max 2048 KB |

**Created status:** `new`.

**Success `200`:** `OrderResource` with `status.value = "new"`.

**Side effects:** User receives `OrderCreatedConfirmationNotification`. If `provider_id` set, that provider gets `NewOrderAssignNotification`. Otherwise `NewOrderCreated` event broadcasts to eligible providers (category match).

**Errors:** `401`, `422` validation, `500`-style `{ "message": "Something went wrong" }` on unexpected failures.

---

### 2.3 Show order (User)

`GET /api/v1/user/orders/{order}`

**Auth:** `user-api`.

**Who:** **order owner only** — others get **`404`**.

**Success `200`:** full `OrderResource` including offers, accepted offer, histories when loaded.

---

### 2.4 Edit order (User)

`POST /api/v1/user/orders/{order}/edit`

**Auth:** `user-api`.

**Who:** order owner only.

**Allowed:** order status must be **`new`** only. Non-owner → **`404`**. Owner but status ≠ `new` → **`403`** with message **`forbidden !!`** (literal key in code — not in `lang/en.json`).

**Body:** same fields as create (`OrderRequest` rules apply on provided fields).

**Success `200`:** updated `OrderResource`.

**Errors (complete):**

| HTTP | Condition | Key / source | English `message` |
|---|---|---|---|
| 401 | No token | — | `Unauthenticated.` |
| 404 | Not owner | — | `Resource not found` |
| 403 | Owner but status ≠ `new` | `forbidden !!` | `forbidden !!` |
| 422 | Validation | Laravel | `Validation Failed` + field errors |

---

### 2.5 Delete order (User)

`DELETE /api/v1/user/orders/{order}`

**Auth:** `user-api`.

**Who:** owner only.

**Rules:** cannot delete if **any offers exist** → `422` / `400` with message key **`you can not delete this order because it has offers`**.

**Success `200`:** `{ "success": true, "message": "<data deleted successfully>" }`.

---

### 2.6 Delete order media (User)

`DELETE /api/v1/user/orders/{order}/{media:uuid}/delete`

**Auth:** `user-api`.

**Who:** owner only.

**Success `200`:** delete success message.

---

### 2.7 Update offer status — accept / reject / cancel (User)

`POST /api/v1/user/orders/{order}/{offer}/update-status`

**Auth:** `user-api`.

**Who:** **order owner only** (`404` for non-owner or wrong offer/order pairing).

**Body:**

| Field | Required | Rules |
|---|---|---|
| `status` | yes | **`accepted`**, **`rejected`**, or **`cancelled`** only |

Sending `pending` or `paid` fails **FormRequest validation** (`422` + `errors.status`) before the Action runs.

#### Accept (`status: accepted`)

**Preconditions (Action, after row lock):**

- Order status must be **`new`**. Otherwise → `422` **`order_already_has_accepted_offer`** — `"This order already has an accepted offer."`
- Offer must be `pending` (not cancelled/rejected/paid).

**Effects:**

- Offer → **`accepted`**
- Order → **`offer_provided`**, `provider_id`, `accepted_offer_id`, `price`, `user_fees`, `provider_fees` set from accepted price
- **Exclusive accept:** all other **`pending`** offers on the order are auto-**rejected**; each losing provider receives **`OrderOfferRejectedNotification`** (same notification as manual reject — sibling auto-reject is indistinguishable in the payload except order/offer ids)
- Winning provider receives **`OrderOfferAcceptedNotification`**
- Concurrent double-accept: row lock ensures **one** success and one **`order_already_has_accepted_offer`** error

**Success `200`:** `{ "success": true, "message": "<data saved successfully>" }` (no refreshed order body).

#### Reject (`status: rejected`)

- Offer → **`rejected`**; provider notified **`OrderOfferRejectedNotification`**
- If this offer was the accepted offer, order **reverts to `new`** (`provider_id`, `accepted_offer_id`, `price` cleared)

#### Cancel offer (`status: cancelled`)

- Offer → **`cancelled`**; order reverts to **`new`**; provider gets **`OrderOfferCanceledNotification`**

**Other errors (complete):**

| HTTP | Condition | Key | English `message` |
|---|---|---|---|
| 401 | No token | — | `Unauthenticated.` |
| 404 | Not owner / wrong offer-order pair | — | `Resource not found` |
| 400 | Offer cancelled, rejected, paid, or wrong order | `you can not update this offer` | `you can not update this offer` |
| 422 | `status` is `pending` or `paid` | `order_offer_status_not_allowed` | This offer status cannot be updated through this endpoint. |
| 422 | Order not `new` on accept (incl. concurrent accept) | `order_already_has_accepted_offer` | This order already has an accepted offer. |
| 422 | FormRequest: invalid `status` value | Laravel | `Validation Failed` + `errors.status` |

Example accept success:

```json
{
  "success": true,
  "data": [],
  "errors": {},
  "message": "Data saved successfully",
  "token": ""
}
```

Example concurrent accept failure:

```json
{
  "success": false,
  "message": "This order already has an accepted offer.",
  "data": [],
  "errors": []
}
```

**Sibling auto-reject:** losing providers receive **`OrderOfferRejectedNotification`** — identical payload to manual reject. Their offer `status.value` becomes **`rejected`**. They should refresh the order/offers list; there is no distinct "auto-rejected" status or notification type.

---

### 2.8 Pay for accepted offer (User)

`POST /api/v1/user/orders/{order}/{offer}/pay`

**Auth:** `user-api`.

**Body:** none.

**Guards (`InitiateOrderPaymentAction`, with row lock):**

- Caller must be order **owner**
- Offer status **`accepted`**
- Offer must belong to order
- **`order.accepted_offer_id === offer.id`** — paying a non-selected accepted offer → **`400`** **`you can not pay for this order`**

**Charged amount:** `order.user_total` (price + user fees).

**Success `200`:** payment initiation object (same shape as Guarantor pay):

```json
{
  "status": "success",
  "driver": "rajhi",
  "url": "https://payments.example/checkout/...",
  "payable": true,
  "transaction_id": null,
  "message": null,
  "data": {}
}
```

Open `url` in WebView. Order status updates only after **`PaymentCompleted`** event:

- Offer → **`paid`**
- Order → **`in_progress`**
- Wallet holds opened
- Both parties → **`OrderPaymentCompletedNotification`**

**Payment failure:** user → **`OrderPaymentFailedNotification`** (no order status change).

**Amount mismatch:** **`OrderPaymentAmountMismatchNotification`** to user; payment rejected by listener.

**Failed initiation:** `{ "success": false, "message": "<driver message>" }`.

---

### 2.9 End and review (User)

`POST /api/v1/user/orders/{order}/end-and-review`

**Auth:** `user-api`.

**Who:** owner only (`404` otherwise).

**Body:**

| Field | Required | Rules |
|---|---|---|
| `rating` | yes | integer 1–5 |
| `comment` | yes | string |

**Allowed order statuses:** **`in_progress`** or **`ended_by_provider`**.

**Blocked while `disputed`:** `422` **`you can not end this order`**.

**Effects:** order → **`ended_by_client`**; review submitted; provider → **`OrderEndedByClientNotification`**.

**Success `200`:** data saved message.

---

### 2.10 Cancel order (User or Provider via User API)

`POST /api/v1/user/orders/{order}/cancel`

**Auth:** `user-api` (User token only in routing — **Provider cancel is not on this route**; see [§2.13](#213-provider-order-mutations-no-apiv1-json-routes-yet)).

**Body:**

| Field | Required | Rules |
|---|---|---|
| `reason` | yes | string, **min 10** |

**Allowed:** only from **`in_progress`** (`OrderStatusEnum::isAllowed`):

- User → **`cancelled_by_client`**
- Provider would use same Action via dashboard with **`cancelled_by_provider`**

**Blocked while `disputed`:** `422` **`you can not cancel this order`**.

**Wallet:** reverses internal holds via `CancelOrderPaymentAction` — **no gateway refund**.

**Other party** → **`OrderCancelledNotification`**.

---

### 2.11 Open dispute (User or Provider)

`POST /api/v1/orders/{order}/dispute`

**Auth:** `auth:sanctum` + `user.active` (not `user-api`).

**Policy `dispute`:** either party, order must be **`in_progress`**. Otherwise **`403`**.

**Body:**

| Field | Required | Rules |
|---|---|---|
| `reason` | yes | string, max 1000 |

**Success `200`:** `OrderResource` with `status.value = "disputed"`, updated `histories`.

**Side effects:**

- End, cancel, and pay frozen while disputed
- Chat remains available ([§5](#5-chat))
- **Other party** + admins with **`manage orders`** → **`OrderDisputedNotification`**. **Opener excluded.**
- Row locked via `lockForUpdate`

**Errors:**

| HTTP | Condition | Message / key |
|---|---|---|
| 403 | Not party or not `in_progress` | `This action is unauthorized.` |
| 422 | Missing/invalid `reason` | Validation Failed |
| 422 | Race / invalid transition | `order.status_transition_not_allowed` → translated `This status transition is not allowed` |

**Who (policy `dispute`):** either party when order status is **`in_progress`** only (not `ended_by_provider`). Non-party or wrong status → **`403`** `"This action is unauthorized."`

**Success `200`:** unified envelope; `data` is show-shaped `OrderResource` **with `histories` loaded**. Key fields after open:

```json
{
  "success": true,
  "data": {
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "status": { "value": "disputed", "label": "Disputed", "color": "danger" },
    "histories": [
      {
        "id": "9f3c2a1b-0000-4000-8000-000000000040",
        "from_status": { "value": "in_progress", "label": "In Progress", "color": "info" },
        "to_status": { "value": "disputed", "label": "Disputed", "color": "danger" },
        "reason": { "value": "Work not delivered as agreed", "label": "Work not delivered as agreed" },
        "notes": null,
        "actor": { "id": 10, "name": "Ahmed", "type": "User" },
        "created_at": "2026-09-02T14:00:00+00:00"
      }
    ]
  },
  "errors": {},
  "message": "",
  "token": ""
}
```

**Errors (complete):**

| HTTP | Condition | Key | English `message` |
|---|---|---|---|
| 401 | Missing token | — | `Unauthenticated.` |
| 403 | Not party or status ≠ `in_progress` | — | `This action is unauthorized.` |
| 422 | `reason` missing / > 1000 chars | Laravel | `Validation Failed` + `errors.reason` |
| 422 | Race: transition no longer allowed | `order.status_transition_not_allowed` | This status transition is not allowed |

`OrderDisputeController` resolves **User or Provider** from `auth()->user()` when using Provider Sanctum tokens on shared routes.

---

### 2.12 Chat — order conversations

Same auth as dispute: **`auth:sanctum` + `user.active`**. User **or** Provider token.

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/v1/chats/orders` | List order conversations |
| `POST` | `/api/v1/chats/orders` | Open/get conversation — body: `{ "order_id": "<uuid>" }` |
| `GET` | `/api/v1/chats/orders/{conversation}` | Messages (paginated) |
| `POST` | `/api/v1/chats/orders/send/{conversation}` | Send message (content and/or files) |

**Chat policy (`OrderPolicy::chat`):** party only, status ∈ **`payment_completed`**, **`in_progress`**, **`ended_by_provider`**, **`disputed`**.

**Handler `OrderChatHandler::canOpen`:** party match only (does not re-check status) — **gate the chat UI on `OrderPolicy::chat` / order status**, not on handler alone.

**Note:** `payment_completed` is in policy/chat list; the payment listener sets **`in_progress`** directly. Treat `payment_completed` as legacy/rare in API responses.

Message shape matches general chat docs (humanized `created_at` + `created_at_iso`).

---

### 2.13 Provider order mutations (no `/api/v1` JSON routes yet)

Implemented today on **Provider web dashboard** (session), paths under `{locale}/provider/dashboard/orders/…`. Responses are **Inertia redirects**, not JSON. Rules below are what a future Provider mobile API **must** preserve.

#### Submit offer

**Action:** `SubmitOfferAction`

| Rule | Error key | English |
|---|---|---|
| Order status must be **`new`** | `order_must_be_new_to_submit_offer` | Offers can only be submitted while the order is new. |
| Provider must not already have **pending or accepted** offer on this order | `provider_already_has_active_offer_on_order` | You already have a pending or accepted offer on this order. |

**Body (validation):** `price` required numeric > 0; `description` required string max 1000.

**Effect:** offer `pending`; client → **`OrderOfferCreatedNotification`**.

#### Edit offer

**Action:** `UpdateProviderOfferAction` — row lock on accepted-offer edits.

| Phase | Behavior |
|---|---|
| **Pending offer** | Price/description update freely |
| **Accepted offer** (order still `offer_provided`, not paid) | **Decrease:** applies immediately; recalculates fees; client → **`OrderAcceptedOfferPriceDecreasedNotification`** with **`old_price`** / **`new_price`** in payload and Firebase |
| **Accepted offer — increase** | **Blocked:** offer → **`cancelled`**, order **reverts to `new`** (clears provider/accepted_offer/price); client → **`OrderAcceptedOfferPriceIncreaseBlockedNotification`** with old and attempted new price — **must accept & pay again** |

No-op edit (unchanged fields) succeeds silently.

Errors: `you can not edit this offer because it has been processed.`, `sorry this offer does not belong to this order.`

#### Delete offer

**Action:** `DeleteProviderOfferAction` — only **`pending`** offers; same ownership errors as edit.

#### Provider end

**Action:** `EndProviderOrderAction`

- Only **`in_progress`**
- Blocked when **`disputed`** → `you can not ed this order` (typo preserved in translation key)
- Order → **`ended_by_provider`**; client → **`OrderEndedByProviderNotification`**

#### Provider cancel

Same `CancelOrderAction` as User API → **`cancelled_by_provider`**, same `in_progress`-only + disputed block, **`reason` min 10** on dashboard form.

---

## 3. Status reference

### Order statuses (`status.value`)

English labels from `lang/en.json`. Colors are Bootstrap tokens from `OrderStatusEnum::color()`.

| Value | Label | Color | Meaning | Terminal? | Reachable via mobile (party) |
|---|---|---|---|---|---|
| `new` | New | primary | Posted; accepting offers | no | create |
| `hold` | Hold | primary | **Enum only — no writer sets this today** | no | — |
| `offer_provided` | Offer Provided | info | Client accepted an offer; awaiting payment | no | accept offer |
| `payment_completed` | Payment Completed | warning | **Enum / analytics; payment listener sets `in_progress` instead** | no | — |
| `in_progress` | In Progress | info | Paid; work active; escrow held | no | payment callback |
| `disputed` | Disputed | danger | Dispute open; end/cancel/pay frozen | no | `POST …/dispute` |
| `ended_by_provider` | Ended By Provider | success | Provider marked complete; client may end+review | no | provider end (web) |
| `ended_by_client` | Ended By Client | success | Client ended with review | **yes** | end-and-review |
| `ended_via_dispute` | Ended Via Dispute | success | Admin resolved — full to provider | **yes** | admin only |
| `cancelled_by_client` | Cancelled By Client | danger | Client cancelled in progress | **yes** | cancel |
| `cancelled_by_provider` | Cancelled By Provider | danger | Provider cancelled in progress | **yes** | provider cancel (web) |
| `cancelled_via_dispute` | Cancelled Via Dispute | danger | Admin resolved — full to client | **yes** | admin only |
| `escalated` | Escalated | dark | Admin escalated; wallet reversed to client | **yes** | admin only |
| `settled` | Settled | success | Admin percentage split | **yes** | admin only |

### Offer statuses (`offers[].status.value`)

| Value | Label | Color | Meaning |
|---|---|---|---|
| `pending` | Pending | primary | Awaiting client decision; may expire (see [§8](#8-known-current-limitations)) |
| `accepted` | Accepted | success | Client selected; awaiting payment |
| `rejected` | Rejected | danger | Client rejected, auto-rejected sibling, or **expired by scheduler** |
| `cancelled` | Cancelled | danger | Client cancelled acceptance or provider price-increase blocked |
| `paid` | Paid | success | Payment captured |

---

## 4. The full lifecycle, step by step

| Order status | Client UI | Provider UI | Enabled actions |
|---|---|---|---|
| `new` | View offers; accept/reject/cancel offers | Submit **one** offer; edit/delete pending offer | User: update-status. Provider: submit/edit/delete (web). |
| `offer_provided` | **Pay** (`user_total`); chat if policy allows | May **decrease** price (notify with amounts) or **increase** (reverts order to `new`) | User: pay. Provider: edit offer (web). |
| `in_progress` | Chat; end+review after provider end; cancel; dispute | Chat; end; cancel; dispute | User: end-and-review (after provider end or directly from in_progress), cancel, dispute. Provider: end, cancel, dispute (web/dispute API). |
| `ended_by_provider` | End+review | Waiting for client | User: end-and-review only |
| `disputed` | Chat; await admin | Chat; await admin | No end/cancel/pay; no second dispute |
| Terminal (`ended_*`, `cancelled_*`, `escalated`, `settled`) | Summary | Summary | Show only — no mutations |

### Offer expiry (scheduled)

Daily job `orders:expire-pending-offers` (00:30) rejects **`pending`** offers older than **`order_offer_expiry_days`** (settings key, **default 7**). Offer → **`rejected`**; provider gets **`OrderOfferRejectedNotification`** (same as manual reject — no distinct "expired" title). Order stays **`new`** if nothing was accepted.

---

## 5. Chat

Gate chat entry on **`OrderPolicy::chat`** statuses: `payment_completed`, `in_progress`, `ended_by_provider`, `disputed`.

While **`disputed`**, chat stays open (confirmed in tests).

Order show **does not include** `conversation_id` — use `POST /api/v1/chats/orders` with `order_id` or list endpoint.

Realtime: private channel **`chats.{conversationId}`** (see general chat / AUTH docs).

---

## 6. Notifications the mobile app should expect

Channels: **database + broadcast** always; **Firebase** when `sendsFirebase` is true below.

Firebase **`screen`** for order notifications: **`orders`** (via `OrderFirebaseNotifiable`).

| Event | Title key → English | Body key → English | Recipient | Firebase? | Payload highlights | Broadcast type |
|---|---|---|---|---|---|---|
| Order created (self) | `order_created` → Order Created | `order_has_been_created` | User (client) | yes | `order_id` | `order created` |
| New order assigned | `new_order_assigned` | `you_have_been_assigned_a_new_order` | Provider (direct assign) | yes | `order_id`, `screen: orders` | `new assigned order` |
| Offer created | `order_offer_created` | `order_offer_has_been_created` | User (client) | yes | `order_id`, `offer_id` | `order offer created` |
| Offer accepted | `order_offer_accepted` | `order_offer_has_been_accepted` | Provider (winner) | yes | `order_id`, `offer_id` | `order offer accepted` |
| Offer rejected / expired / sibling auto-reject | `order_offer_rejected` | `order_offer_has_been_rejected` | Provider | yes | `order_id`, `offer_id` | `order offer rejected` |
| Offer cancelled | `order_offer_canceled` | `order_offer_has_been_canceled` | Provider | yes | `order_id`, `offer_id` | `order offer canceled` |
| Accepted price **decreased** | `order_accepted_offer_price_decreased` | `…_body` with `:old_price`, `:new_price` | User | yes | `old_price`, `new_price`, `offer_id` | `new assigned order` (*) |
| Accepted price **increase blocked** | `order_accepted_offer_price_increase_blocked` | `…_body` with old + attempted new | User | yes | same price fields | `order accepted offer price increase blocked` |
| Payment completed | `order_payment_completed` | `order_payment_has_been_completed` | **Both** parties | yes | `order_id`, `final_status` | `order payment completed` |
| Payment failed | `order_payment_failed` | `order_payment_failed_body` | User | yes | `order_id`, `offer_id` | `order payment failed` |
| Payment amount mismatch | `order_payment_amount_mismatch` | `…_body` with paid vs expected | User | yes | amounts in payload | `order payment amount mismatch` |
| Ended by provider | `order_ended_by_provider` | `order_has_been_ended_by_provider` | User | yes | `order_id` | `order ended by provider` |
| Ended by client + review | `order_ended_by_client` | `order_has_been_ended_by_client_with_review` | Provider | yes | `order_id`, `rating` | `order ended by client` |
| Cancelled | `order_cancelled` | `order_has_been_cancelled` | Other party | yes | `order_id`, `cancellation_reason` | `order cancelled` |
| Dispute opened | `order_disputed_title` | `order_disputed_body` | **Other party** + admins (`manage orders`) | yes (parties + admin) | `order_id`, `reason`, `final_status: disputed` | `order disputed` |
| Dispute resolved (any of 4) | outcome-specific `order_dispute_resolved_*` | outcome-specific body | **Both parties** | yes (parties only) | `order_id`, `resolution`, `final_status`; split adds percentages/amounts | `order dispute resolved` |

(*) `OrderAcceptedOfferPriceDecreasedNotification::broadcastType()` currently returns `'new assigned order'` in code — treat as copy/paste oversight; listen by title key or payload shape.

### Deprecated / unused notification class

`OrderAcceptedOfferUpdatedNotification` exists but is **not dispatched** after the asymmetric price-change work — use the **decrease** / **increase blocked** notifications instead.

### Admin-only

`StuckOrderSettlementsNotification` — admins when paid ended orders remain unsettled past dispute window; not for mobile party apps.

---

## 7. Dispute feature — complete reference

Resolution is **Dashboard / Admin only** (`PUT /dashboard/orders/{order}/resolve-dispute`, permission **`manage orders`**). Mobile opens disputes and **observes** the resulting status, `dispute_resolution`, wallet balance, and notifications.

### While status is `disputed`

| Action | Allowed? | How blocked |
|---|---|---|
| **Show / list** | yes | — |
| **Chat** | yes | `OrderPolicy::chat` includes `disputed` |
| **Open another dispute** | no | Policy: status must be `in_progress` → **403** |
| **End-and-review** | no | Action: **`422`** `you can not end this order` |
| **Cancel** | no | Action: **`422`** `you can not cancel this order` |
| **Pay** | no | Order not in `offer_provided` / offer not payable |

### Admin resolution outcomes (what mobile sees)

Mobile has **no resolve endpoint**. After Admin resolves, poll `GET /api/v1/user/orders/{order}` or handle **`OrderDisputeResolvedNotification`**.

For **percentage splits**, `OrderResource.dispute_resolution` is the **primary source** for past outcomes (always present on show when split columns are set). Notification payload carries the same numbers at resolution time.

```json
"dispute_resolution": {
  "user_percentage": 60,
  "provider_percentage": 40,
  "user_amount": "157.50",
  "provider_amount": "90.00"
}
```

`provider_percentage` is derived as `100 - user_percentage` (not stored separately).

| Admin outcome | Resulting `status.value` | Terminal? | History `reason.value` (exact) | Other fields on show |
|---|---|---|---|---|
| Full to provider | `ended_via_dispute` | yes | `dispute_resolved_full_provider` | `dispute_resolution`: `null` |
| Full to client | `cancelled_via_dispute` | yes | `dispute_resolved_full_user` | `cancellation_reason` may reflect outcome; `dispute_resolution`: `null` |
| Percentage split | `settled` | yes | `dispute_resolved_percentage_split:{userPct}/{providerPct}` | **`dispute_resolution`** object |
| Escalate | `escalated` | yes | `dispute_escalated_to_court` | `dispute_resolution`: `null` |

All four paths notify **both parties** with `OrderDisputeResolvedNotification`.

`resolution` in notification payload: `full_user`, `full_provider`, `percentage_split`, `escalate`.

### Dispute-related notifications

| Notification | Trigger | Recipients | Database / broadcast `payload()` | Firebase push? |
|---|---|---|---|---|
| `OrderDisputedNotification` | Dispute opened | **Other party** + **Admins** (`manage orders`). **Opener excluded.** | `order_id`, `reason` (user string), `final_status` (`disputed`) | **User, Provider, Admin** (`orderPartyOrAdminReceivesFirebase`) |
| `OrderDisputeResolvedNotification` | Admin resolves (any of 4) | **Both parties** | Always: `order_id`, `resolution`, `final_status`. Split adds percentages + amounts | **User, Provider only** (`orderPartyReceivesFirebase`) |

**English title/body keys** (`lang/en.json`):

| Notification | Title key → English | Body key → English |
|---|---|---|
| Disputed | `order_disputed_title` → Order disputed | `order_disputed_body` → A dispute was opened on your order |
| Resolved full user | `order_dispute_resolved_full_user_title` → Dispute resolved — client favored | `…_body` → The admin resolved the dispute in favor of the client |
| Resolved full provider | `order_dispute_resolved_full_provider_title` → Dispute resolved — provider favored | `…_body` → The admin resolved the dispute in favor of the provider |
| Escalated | `order_dispute_resolved_escalated_title` → Dispute escalated | `…_body` → The dispute was escalated for external resolution |
| Percentage split | `order_dispute_resolved_percentage_split_title` → Dispute settled via split | `…_body` → Client :user_percentage% / Provider :provider_percentage% |

**Firebase `data` subset:**

| Notification | FCM data fields |
|---|---|
| Disputed | `order_id`, `final_status`, `screen: orders` |
| Dispute resolved | `order_id`, `resolution`, `final_status`, `screen: orders`; split adds stringified percentages and formatted amounts |

Broadcast types: `order disputed`, `order dispute resolved`.

### Dispute error envelope quick reference

| Scenario | HTTP | Example `message` |
|---|---|---|
| Policy deny (wrong party / wrong status) | 403 | `This action is unauthorized.` |
| End while disputed | 422 | `you can not end this order` |
| Cancel while disputed | 422 | `you can not cancel this order` |
| Missing `reason` on `/dispute` | 422 | `Validation Failed` + field errors |
| Race on open | 422 | `This status transition is not allowed` |

---

## 8. Known current limitations

1. **No gateway / card refund.** Cancel, dispute full-to-client, and escalate reverse **internal wallet holds** only. Do not promise card refunds unless a future payment API adds them.

2. **Provider order mutations lack `/api/v1` JSON routes.** Submit/edit/delete offer, provider end, and provider cancel are **Provider dashboard (session)** only. Chat and dispute use shared Sanctum.

3. **One active offer per provider per order.** While an offer is **`pending`** or **`accepted`**, another submit attempt fails with **`provider_already_has_active_offer_on_order`**.

4. **Exclusive accept.** Accepting one offer auto-rejects other pending offers; losing providers get the **same rejection notification** as a manual reject.

5. **Post-accept price increase is blocked.** Increase cancels the offer and reverts the order to **`new`**; client must accept (same or new) offer and pay again. Decrease applies immediately with explicit old/new notification.

6. **Pay requires `accepted_offer_id` match.** Paying an offer that is accepted but not the order's selected offer returns **`you can not pay for this order`**.

7. **Pending offer expiry.** Default **7 days** (`order_offer_expiry_days` setting); expired offers become **`rejected`** with standard rejection notification.

8. **Enum statuses `hold` and `payment_completed`.** Present in enum/dashboard aggregates; happy-path payment sets **`in_progress`** directly.

9. **`OrderAcceptedOfferUpdatedNotification`.** Legacy class — not sent; use price decrease / increase blocked notifications.

10. **`histories` not on regular show.** `GET /api/v1/user/orders/{order}` does not eager-load `histories` today. Only dispute-open response includes them. Use notifications + status polling for timeline UI.

11. **Cancel / end error strings.** Some keys remain literal English in code (`you can not cancel this order`, `you can not end this order`, `forbidden !!`, `you can not ed this order`) — not all are in `lang/en.json`.

12. **Sibling auto-reject vs manual reject.** Same notification class — UI cannot distinguish without local state.

13. **Chat before payment.** Policy allows chat from `payment_completed` enum value, but happy-path payment sets `in_progress`. Chat is effectively available from **`in_progress`**, **`ended_by_provider`**, and **`disputed`** in practice.

---

## Quick UI checklist

| Order status | Client: offers | Client: pay | Client: end | Client: cancel | Client: dispute | Chat |
|---|---|---|---|---|---|---|
| `new` | accept/reject/cancel | no | no | no | no | no (*) |
| `offer_provided` | no | **yes** | no | no | no | policy |
| `in_progress` | no | no | end+review | **yes** | **yes** | **yes** |
| `ended_by_provider` | no | no | **yes** | no | no | policy |
| `disputed` | no | no | no | no | no | **yes** |
| terminal | no | no | no | no | no | no (entry) |

(*) Chat policy includes `payment_completed` — rare status value.

| Provider (web today) | `new` | `offer_provided` | `in_progress` | `disputed` |
|---|---|---|---|---|
| Submit/edit/delete offer | yes | edit only | no | no |
| End | no | no | yes | no |
| Cancel | no | no | yes | no |
| Dispute (API) | no | no | yes | no |
