# Guarantor — Mobile API reference

Standalone contract for a Flutter (or other mobile) client. Every path, field name, enum string, validation rule, and error message below is taken from the **current** API routes, request validation, JSON resources, authorization rules, and notification payloads. Where the code is ambiguous, this document says so instead of guessing.

**Base URL prefix:** `/api/v1`

**Auth:** `Authorization: Bearer {access_token}` (Sanctum). Same token as the rest of the User mobile API (see `docs/mobile/AUTH_FLOW.md`). Inactive / banned Users receive `403` and the token is revoked (see [Common envelopes](#common-envelopes)).

**Locale:** send `Accept-Language` (`en`, `ar`, `ur`, `hi`, …). Status labels, validation messages, and notification title/body strings follow this locale.

**Rate limit:** API routes are throttled at **60 requests per minute**.

**IDs:** guarantor requests, installments, conversations, messages, and media `id` values are **UUIDs**.

**Do not use create on the User app.** Product intent is that a Provider creates the request and a User is the counterparty (payer). The create endpoints currently have **no extra role check**: any authenticated Sanctum User can call them. Sanctum API tokens in this product are issued for Users; Providers use a session login and do not currently issue Sanctum tokens. Still: **do not expose a create flow in the User app.**

---

## Table of contents

1. [Overview](#1-overview)
2. [Common envelopes](#common-envelopes)
3. [Shared JSON objects](#shared-json-objects)
4. [Full endpoint reference](#2-full-endpoint-reference)
5. [Status reference](#3-status-reference)
6. [Lifecycle (UI)](#4-the-full-lifecycle-step-by-step)
7. [Company installments](#5-company-installments-in-detail)
8. [Chat](#6-chat)
9. [Notifications](#7-notifications-the-mobile-app-should-expect)
10. [`authorization_type`](#8-field-reference-authorization_type)
11. [Known current limitations](#9-known-current-limitations)

---

## 1. Overview

A **guarantor request** is an escrow-style contract between two parties on the platform:

| Role | Who | What they do |
|---|---|---|
| **Requester** | The party who creates the request | Describes the work, uploads KYC / signature, waits for admin review, then waits for the other party to accept and pay. Can edit/delete only while the request is waiting for admin. Can **end** the request after money is in escrow (in progress / overdue). |
| **Counterparty** | Always a **registered User**, looked up by phone | Reviews an admin-approved request, **accepts or rejects** it, then **pays** (full amount for Individual, installment-by-installment for Company). Can **end** after money is in escrow. |
| **Admin** | Dashboard only — **no mobile endpoints** | Reviews new requests (`pending_admin` → approve or reject), can cancel later, can release company installment funds. Mobile only **observes** the resulting status. |

There is **no third “guarantor company” actor** on the mobile API. “Company” is a **request type**, not a login role.

### Two request types

| Type value | Meaning | How money moves |
|---|---|---|
| `individual` | Person-to-person guarantee | One payment of **amount + fees** after accept. Request moves to `in_progress` when the gateway confirms that payment. |
| `company` | Company / project contract with a schedule | Counterparty pays **each installment amount only** (fees are not added to the charge). Paying the first installment moves the request to `in_progress` (same as Individual after payment). If the request was `overdue`, payment also clears `overdue_at`. |

Fees are **always `10.00` on create**. The client cannot send `fees`. `total` is `amount + fees`.

### Who is the counterparty?

`counterparty_phone` must belong to an existing **User** (not a Provider). You cannot name yourself if you are logged in as that same User. Invalid / unknown phones fail validation (see create endpoints).

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

Create / show / list / pay **do not** set a human `message` in the controller. Expect `message` to be an empty string on those successes. Delete endpoints **do** set `message`.

### HTTP 401 — missing / invalid token

Laravel default (not the success envelope):

```json
{
  "message": "Unauthenticated."
}
```

### HTTP 403 — policy / authorization

When the caller is authenticated but not allowed (wrong party, wrong status for that action), Laravel returns:

```json
{
  "message": "This action is unauthorized."
}
```

When a **User** account is inactive / banned, middleware returns `403` with:

```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "<status-specific rejection message>",
  "token": ""
}
```

(The exact `message` depends on the user’s account status.)

### HTTP 404 — unknown UUID

```json
{
  "success": false,
  "message": "Guarantor request not found",
  "data": [],
  "errors": []
}
```

For a missing installment UUID: `"message": "Installment not found"`.

For other missing models: `"message": "Resource not found"`.

### HTTP 422 — validation

```json
{
  "success": false,
  "data": [],
  "errors": {
    "amount": ["The amount field is required."]
  },
  "message": "Validation Failed",
  "token": ""
}
```

`errors` is a map of **field name → array of strings**. Standard Laravel messages apply unless a custom message is listed on the endpoint.

### HTTP 422 — domain / business rule

Module domain exceptions (`GuarantorException`, `OrdersException`, `WalletException`, …) and the unified `ApiException` all render the same envelope:

```json
{
  "success": false,
  "message": "This status transition is not allowed",
  "data": [],
  "errors": []
}
```

`message` is a translated string. `errors` is usually `[]` for domain failures (not a field map).

Note: `errors` here is a **JSON array** `[]`, not `{}`.

### HTTP 413 / validation — body larger than PHP `post_max_size`

Same validation envelope, `errors.files` = `["One of your files exceeds the upload limit."]`. Normal uploads should stay within the per-file `max:` rules (5 MB or 10 MB as listed per field) so this path is only a safety net.

---

## Shared JSON objects

### Status / type object

Most enums serialize as:

```json
{ "value": "accepted", "label": "Accepted", "color": "#8b5cf6" }
```

`label` is translated. `color` is a hex string from the server (for badges). `authorization_type` has **no** `color` — only `value` and `label`.

### Participant (`requester` / `counterparty`)

```json
{
  "id": 42,
  "name": "Ahmed Mohamed",
  "type": "user",
  "image": "https://example.com/storage/avatars/ahmed.jpg",
  "phone": "0501234567"
}
```

| Field | Type | Notes |
|---|---|---|
| `id` | number (User/Provider primary key) | Not a UUID |
| `name` | string | `name`, or first + last name if `name` is empty |
| `type` | `"user"` \| `"provider"` | |
| `image` | string \| null | Profile image URL |
| `phone` | string \| null | |

On **chat** conversation participants, `phone` is **not** included; chat adds no extra fields beyond `id`, `name`, `type`, `image`.

### Media file

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000010",
  "name": "signature",
  "collection_name": "signature",
  "file_name": "signature.pdf",
  "mime_type": "application/pdf",
  "type": "application",
  "url": "https://example.com/storage/...",
  "extension": "pdf",
  "size": "100 KB"
}
```

| Field | Type | Notes |
|---|---|---|
| `id` | UUID string | This is the media UUID. Use it in `DELETE .../media/{id}`. |
| `collection_name` | string | See collections below |
| `size` | string | Human-readable (not bytes) |
| `type` | string | Present on the resource; typically a MIME class such as `image` / `application`. If a client sees `null`, treat as unspecified — there is no dedicated accessor in this app. |

**Collections on the guarantor request itself:** `signature` (create), `files` (update uploads).

**Collections on company details** (nested under `company_detail.media`, **not** request `media`): `authorized_id`, `contracts`, `iban_certificates`, `company_documents`.

The delete-media URL only targets media on the **request**. Company-detail files are not deleted by that endpoint.

### Installment

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000020",
  "order": 1,
  "amount": "10000.00",
  "due_date": "2026-09-01",
  "status": { "value": "pending", "label": "Pending", "color": "#f59e0b" },
  "paid_at": null,
  "released_at": null,
  "overdue_notified_at": null,
  "escalated_at": null,
  "is_past_due": false,
  "created_at": "2026-08-15T10:00:00+00:00"
}
```

| Field | Type | Notes |
|---|---|---|
| `amount` | decimal string | e.g. `"10000.00"`. Parse as number. |
| `due_date` | `YYYY-MM-DD` | Date only |
| `is_past_due` | boolean | `true` when `due_date` is in the past **and** installment status is still `pending`. Use this for UI — do **not** wait for installment `status` to become `overdue` (that value is almost never stored). |
| `overdue_notified_at` | ISO-8601 \| null | Set when a due/overdue reminder job has notified |
| `escalated_at` | ISO-8601 \| null | Set when an unpaid installment past 14 days overdue was escalated to admins (visibility only) |

### Company detail

Returned on company requests when loaded (create company, show, update).

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000030",
  "company_name": "Acme Contracting",
  "commercial_register": "1010123456",
  "authorized_name": "Khalid Al Saud",
  "authorized_id_number": "1098765432",
  "authorization_type": { "value": "power_of_attorney", "label": "Power of Attorney" },
  "requester_account_holder": "Acme Contracting",
  "requester_iban": "SA0380000000608010167519",
  "counterparty_account_holder": "Ahmed Mohamed",
  "counterparty_iban": null,
  "region": { "id": 1, "title": "Riyadh" },
  "city": { "id": 3, "title": "Riyadh", "region_id": 1 },
  "media": []
}
```

**Ambiguous:** `region` and `city` are **not** wrapped in a dedicated resource. They are the geo models as JSON. At minimum expect `id`. Translated `title` may appear when translations are loaded. **Do not** assume a frozen geo shape. They are omitted when those relations are not loaded (create-company loads company media but **not** region/city; **show** does load them).

`counterparty_iban` may be `null` (field is optional on create).

### Status history row

Loaded on **show** and **update**, not on create.

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000040",
  "from_status": null,
  "to_status": { "value": "pending_admin", "label": "Pending Admin Review", "color": "#f59e0b" },
  "reason": null,
  "notes": null,
  "actor": { "id": 10, "name": "Provider Co", "type": "provider", "image": null, "phone": "0501112233" },
  "created_at": "2026-08-15T10:00:00+00:00"
}
```

`from_status` is `null` on the first history row (creation).

### Guarantor request (full resource)

Keys that are **always** present:

| Field | Type | Notes |
|---|---|---|
| `id` | UUID | |
| `type` | object | `individual` or `company` |
| `status` | object | see status table |
| `title` | string | Individual: client-sent title. Company: **copied from `project_type`** (there is no separate title field on create). |
| `description` | string | Individual: client-sent. Company: **always `""`** on create. |
| `amount` | decimal string | Individual: requested amount. Company: `total_amount` from create. |
| `fees` | decimal string | `10.00` at create |
| `total` | decimal string | `amount + fees` |
| `project_type` | string \| null | Company: same as title. Individual: usually `null`. |
| `cancellation_reason` | string \| null | Set when admin cancels |
| `overdue_at` | ISO-8601 \| null | |
| `ended_at` | ISO-8601 \| null | |
| `cancelled_at` | ISO-8601 \| null | |
| `rejected_at` | ISO-8601 \| null | Admin or counterparty reject |
| `refunded_at` | ISO-8601 \| null | |
| `created_at` | ISO-8601 | |

Keys present **only when that relation was loaded** (omitted otherwise; do not assume every key on every endpoint):

| Field | When present |
|---|---|
| `requester` | list, create, show, update |
| `counterparty` | list, create, show, update |
| `installments` | create company, show, update (not create individual) |
| `company_detail` | create company, show, update; `null` for individual |
| `status_histories` | show, update |
| `media` | list, create, show, update |
| `installments_count` | **list only** |

**Not in the resource:** conversation id. Show internally loads the chat relation but **does not serialize it**. Open chat via the chat endpoints.

### List pagination (guarantor index)

```json
{
  "items": [ /* Guarantor request objects */ ],
  "total": 25,
  "count": 10,
  "per_page": 10,
  "current_page": 1,
  "last_page": 3,
  "has_more_pages": true
}
```

Chat list / messages use a nested `paginate` object instead (see chat endpoints).

---

## 2. Full endpoint reference

All of these sit behind `auth:sanctum` + the User-active check.

Who may succeed is from the **policy** (403 if it fails) plus extra business checks (422).

---

### 2.1 List my requests

`GET /api/v1/guarantor`

**Auth:** Sanctum bearer.

**Who:** any authenticated User. Returns requests where the token owner is **requester or counterparty** (unless `role` narrows it). No extra status gate.

**Query parameters** (none are validated against enums; invalid `status` / `type` simply yield an empty list):

| Param | Type | Default | Notes |
|---|---|---|---|
| `per_page` | int | `10` | |
| `status` | string, comma-separated string, or array | — | e.g. `accepted` or `accepted,in_progress` or `status[]=accepted` |
| `type` | `individual` \| `company` | — | Invalid values are ignored (no match) |
| `role` | `requester` \| `counterparty` | — | Any other value is ignored; list stays “either party” |
| `search` | string | — | `title` LIKE `%search%` |
| `date_from` | date string | — | `created_at` date ≥ |
| `date_to` | date string | — | `created_at` date ≤ |

**Request body:** none.

**Success `200`:** envelope `data` = pagination object above. List items include `requester`, `counterparty`, `installments` (full array), `media`, `installments_count`. They do **not** include `company_detail` or `status_histories`.

**Errors:** `401`. Inactive user `403`.

---

### 2.2 Create Individual

`POST /api/v1/guarantor/individual`

**Auth:** Sanctum bearer.

**Who:** any authenticated actor (no policy). **Do not expose this in the User app.**

**Content-Type:** `multipart/form-data` (file upload).

**Created status:** `pending_admin` (not `new`).

**Body:**

| Field | Type | Required | Rules | Example |
|---|---|---|---|---|
| `counterparty_phone` | string | yes | Must be a valid phone (KSA by default, e.g. `05XXXXXXXX`). Must belong to an existing **User** other than the caller (if the caller is also a User). | `0501234567` |
| `amount` | number | yes | numeric, min `1` | `5000` |
| `title` | string | yes | max 255 | `Apartment renovation guarantee` |
| `description` | string | yes | max 2000 | `Guarantee for a 3-month finishing contract` |
| `signature` | file | yes | mimes: `jpg,jpeg,png,pdf`; max **5120** KB (5 MB) | (binary) |

**Success `200`:** `data` is a guarantor resource with `type.value = "individual"`, `status.value = "pending_admin"`, `fees` typically `"10.00"`, `total` = amount + fees, `requester`, `counterparty`, `media` (signature). No `installments` / `company_detail` / `status_histories` keys (relations not loaded).

Example `data` (truncated):

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000001",
  "type": { "value": "individual", "label": "Individual", "color": "#3b82f6" },
  "status": { "value": "pending_admin", "label": "Pending Admin Review", "color": "#f59e0b" },
  "title": "Apartment renovation guarantee",
  "description": "Guarantee for a 3-month finishing contract",
  "amount": "5000.00",
  "fees": "10.00",
  "total": "5010.00",
  "project_type": null,
  "cancellation_reason": null,
  "requester": { "id": 10, "name": "Provider Co", "type": "user", "image": null, "phone": "0501110000" },
  "counterparty": { "id": 20, "name": "Ahmed Mohamed", "type": "user", "image": null, "phone": "0501234567" },
  "media": [],
  "overdue_at": null,
  "ended_at": null,
  "cancelled_at": null,
  "rejected_at": null,
  "refunded_at": null,
  "created_at": "2026-08-15T10:00:00+00:00"
}
```

(`requester.type` is `"user"` when the token is a User token.)

**Errors:**

| Status | When | Message / key |
|---|---|---|
| 401 | No token | `Unauthenticated.` |
| 422 | Invalid phone format | `errors.counterparty_phone`: `"The phone number is invalid."` |
| 422 | Phone not a User, or is the same User | `errors.counterparty_phone`: `"The selected user could not be found."` |
| 422 | Missing/invalid fields | Standard Laravel messages on `amount`, `title`, `description`, `signature` |
| 422 | Counterparty User missing at write time (race) | envelope `message`: `"The counterparty phone number is not registered in the system"` |
| 403 | Same person as requester and counterparty (second guard) | envelope `message`: `"You are not authorized to perform this action"` |

The counterparty is **not** notified on create. Only the requester gets “submitted / pending admin” (see [Notifications](#7-notifications-the-mobile-app-should-expect)). Admins with the `manage guarantors` permission **are** notified (`GuarantorPendingReviewNotification`, Firebase enabled).

---

### 2.3 Create Company

`POST /api/v1/guarantor/company`

**Auth:** Sanctum bearer.

**Who:** any authenticated actor (no policy). **Do not expose this in the User app.**

**Content-Type:** `multipart/form-data`.

**Created status:** `pending_admin`.

**There is no `title` / `description` / `amount` field.** Send `project_type` and `total_amount`. The API stores `title = project_type`, `description = ""`, `amount = total_amount`.

**Body:**

| Field | Type | Required | Rules | Example |
|---|---|---|---|---|
| `counterparty_phone` | string | yes | Same as Individual | `0501234567` |
| `project_type` | string | yes | max 255 | `Construction` |
| `total_amount` | number | yes | numeric, min `1` | `30000` |
| `installments` | array | yes | min 1, max **12** items | see below |
| `installments.*.order` | int | yes | integer, min `1` | `1` |
| `installments.*.amount` | number | yes | numeric, min `1` | `10000` |
| `installments.*.due_date` | date | yes | must be a **date after today** (not today). Eastern digits are normalized to Western digits before validation. | `2026-09-15` |
| `company_name` | string | yes | max 255 | `Acme Contracting` |
| `commercial_register` | string | yes | max 255 | `1010123456` |
| `region_id` | int | no | must exist in `regions` | `1` |
| `city_id` | int | no | must exist in `cities` | `3` |
| `authorized_name` | string | yes | max 255 | `Khalid Al Saud` |
| `authorized_id_number` | string | yes | max 50 | `1098765432` |
| `authorization_type` | string | yes | `power_of_attorney` or `agency` | `power_of_attorney` |
| `requester_account_holder` | string | yes | max 255 | `Acme Contracting` |
| `requester_iban` | string | yes | max 50 | `SA0380000000608010167519` |
| `counterparty_account_holder` | string | yes | max 255 | `Ahmed Mohamed` |
| `counterparty_iban` | string | **no** | max 50 | `SA0380000000608010167520` |
| `signature` | file | yes | jpg/jpeg/png/pdf, max 5120 KB | |
| `authorized_id` | file | yes | jpg/jpeg/png/pdf, max 5120 KB | |
| `contracts` | file[] | yes | array min 1; each jpg/jpeg/png/pdf, max **10240** KB (10 MB) | |
| `iban_certificate` | file | no | jpg/jpeg/png/pdf, max 5120 KB | |
| `company_documents` | file[] | no | each jpg/jpeg/png/pdf, max 10240 KB | |

**Custom validation:**

- Sum of `installments.*.amount` must equal `total_amount` (rounded to 2 decimals). Error key: **`installments`** (the array, not a nested index). Message: `"The sum of installment amounts must equal the total contract amount"`.
- Due date messages:
  - required: `"Each installment must have a due date."`
  - invalid date: `"Each installment due date must be a valid date."`
  - not after today: `"Each installment due date must be a date after today."`

**Success `200`:** resource with `type.value = "company"`, `status.value = "pending_admin"`, `title` equal to `project_type`, `description` `""`, `installments` array, `company_detail` (media loaded; region/city usually **absent** on this response), request `media` (signature).

**Errors:** same phone / 401 / 403 cases as Individual, plus 422 on any company field and the sum mismatch.

---

### 2.4 Show

`GET /api/v1/guarantor/{guarantorRequest}`

**Auth:** Sanctum bearer.

**Who:** **requester or counterparty** only. Any status. Others → `403`.

**Success `200`:** full resource including `installments`, `company_detail` (with region, city, media), `status_histories`, `media`. No `installments_count`.

**Errors:** `401`, `403` (not a party), `404` (`Guarantor request not found`).

---

### 2.5 Update

`POST /api/v1/guarantor/{guarantorRequest}`  
(not PUT/PATCH)

**Auth:** Sanctum bearer.

**Who (policy):** **requester** and status **`pending_admin`**. Otherwise `403`.

A second check also requires `pending_admin` and returns `422` with `"You can only update requests pending admin review"` if status is not that (should not happen if policy is enforced).

**Content-Type:** `multipart/form-data` if sending files; JSON is fine if only scalars.

**Body (all optional — `sometimes`):**

| Field | Type | Rules |
|---|---|---|
| `title` | string | max 255 |
| `description` | string | max 2000 |
| `amount` | number | numeric, min `1` |
| `project_type` | string \| null | max 255 |
| `files` | file[] | nullable array; each jpg/jpeg/png/pdf, max 5120 KB. Stored in collection `files` (does not replace `signature`). |

**Cannot update via this endpoint:** company KYC fields, IBANs, `authorization_type`, installments, counterparty, fees, status.

**Success `200`:** show-shaped resource (includes `status_histories`, `company_detail`, `installments`).

**Errors:** `401`, `403` (not requester, or status ≠ `pending_admin`), `404`, `422` validation, `422` domain message above.

---

### 2.6 Delete request

`DELETE /api/v1/guarantor/{guarantorRequest}`

**Auth:** Sanctum bearer.

**Who (policy):** **requester** and status **`pending_admin`**. Otherwise `403`.

Second check: `422` `"You can only delete requests pending admin review"`.

**Success `200`:**

```json
{
  "success": true,
  "message": "Guarantor request deleted successfully"
}
```

(Soft-deleted; further GET returns 404.)

**Errors:** `401`, `403`, `404`, `422` as above.

---

### 2.7 Update status (accept / reject / end)

`POST /api/v1/guarantor/{guarantorRequest}/status`

**Auth:** Sanctum bearer.

**Who (policy):** **either party**, any status → policy passes. **Actual transitions** are then restricted (see [matrix](#mobile-party-transitions)). Disallowed transition → `422` `"This status transition is not allowed"`. Same status again → `422` `"The request is already in this status"`.

Admin-only transitions (`approved_by_admin`, `rejected_by_admin`, `cancelled`, `pending_admin`, `new`, `disputed`, `escalated`, `settled`, `ended_via_dispute`, `cancelled_via_dispute`) **will validate as enum values** but **fail the transition check** for mobile parties.

**Body:**

| Field | Type | Required | Rules |
|---|---|---|---|
| `status` | string | yes | One of: `new`, `pending_admin`, `approved_by_admin`, `rejected_by_admin`, `accepted`, `rejected`, `in_progress`, `overdue`, `disputed`, `ended`, `ended_via_dispute`, `cancelled`, `cancelled_via_dispute`, `escalated`, `settled` |
| `reason` | string | **required when** `status` is `rejected_by_admin`, `rejected`, or `cancelled` | nullable otherwise; max 1000 |
| `notes` | string | no | max 2000 |

**What mobile should actually send:**

| Caller | Current status | Send `status` | `reason` |
|---|---|---|---|
| Counterparty | `approved_by_admin` | `accepted` | no |
| Counterparty | `approved_by_admin` | `rejected` | **yes** |
| Requester or counterparty | `in_progress` or `overdue` | `ended` | no |
| Requester or counterparty | `in_progress` or `overdue` | `disputed` | use `POST .../dispute` with `reason` instead |
| Anyone | anything else | — | do not call; it 422s |

**Do not send `cancelled` from mobile.** Validation may pass (with `reason`) but the transition is not allowed for requester/counterparty. Cancel is admin/Dashboard only.

**Success `200`:** show-shaped guarantor resource.

**Side effects:**

- `accepted` → a chat conversation is created (if not already). Requester is notified. Counterparty is not sent an “you accepted” notification.
- `rejected` → requester notified. Terminal.
- `ended` → both parties notified with `GuarantorEndedNotification` (title/body of “ended”). Individual: escrow wallets settle. Company: the **latest `paid` installment** (if any) is released to the requester. Cancel is a **separate** notification (`GuarantorCancelledNotification`); it does **not** reuse Ended.

**Errors:** `401`, `403` (not a party), `404`, `422` validation (`status` required / invalid enum; `reason` required for reject), `422` transition / already-set.

---

### 2.8 Open dispute

`POST /api/v1/guarantor/{guarantorRequest}/dispute`

**Auth:** Sanctum bearer.

**Who (policy):** **either party** (requester or counterparty) when request status is **`in_progress`** or **`overdue`**. Otherwise `403`.

**Body:**

| Field | Type | Required | Rules |
|---|---|---|---|
| `reason` | string | yes | max 1000 |

**Success `200`:** show-shaped guarantor resource with `status.value` = `disputed`.

**Side effects:**

- Request status → `disputed` (non-terminal freeze: End and further installment payments blocked; chat and admin cancel remain available).
- The **other party** receives `GuarantorDisputedNotification`.
- Admins with `manage guarantors` receive the same disputed notification.
- Status history records the reason.

**Errors:** `401`, `403` (wrong party or status not `in_progress`/`overdue`), `404`, `422` validation on `reason`.

---

### 2.9 Pay Individual (full amount)

`POST /api/v1/guarantor/{guarantorRequest}/pay`

**Auth:** Sanctum bearer.

**Who (policy):** **counterparty** and request status **`accepted`**. Otherwise `403`.

Extra check: status must still be `accepted` → else `422` `"This status transition is not allowed"`.

**Body:** none.

**Charged amount:** `total` (**amount + fees**), not `amount` alone.

**Success `200`:** `data` is a **payment initiation** object — **not** `{ "payment_url": ... }`. Keys:

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

| Field | Type | Notes |
|---|---|---|
| `status` | string | `"success"` or `"failed"` |
| `driver` | string | Server default (env `PAYMENT_DRIVER`, typically `rajhi`; also `paytabs` / `testing`) |
| `url` | string | **Open this in a WebView / browser.** Empty on failure. |
| `payable` | bool | `false` if initiation failed |
| `transaction_id` | string \| null | Gateway-dependent |
| `message` | string \| null | Failure detail |
| `data` | object | Gateway extra; testing driver includes `amount` and `payment_id` |

After the user completes checkout, the **request does not update until the gateway callback**. Both parties receive **`GuarantorPaymentReceivedNotification`** when payment is successfully applied. Poll `GET` show or wait for the push; Individual should move to `in_progress`.

**Errors:** `401`, `403` (not counterparty, or status ≠ `accepted`), `404`, `422` wrong status.

Do **not** call this for Company requests. Use installment pay.

---

### 2.10 Delete media (request files)

`DELETE /api/v1/guarantor/{guarantorRequest}/media/{media}`

`{media}` is the media **UUID** (the `id` field on media objects).

**Auth:** Sanctum bearer.

**Who (policy):** **requester** and status **`pending_admin`**. Otherwise `403`.

Second check: `422` `"You can only delete media while pending admin review"`.

**Success `200`:** `{ "success": true, "message": "Media deleted successfully" }`.

**Errors:** `401`, `403`, `404` (request or media), `422`.

Company KYC files live on `company_detail`, not on the request — this URL will not remove those.

---

### 2.11 List installments

`GET /api/v1/guarantor/{guarantorRequest}/installments`

**Auth:** Sanctum bearer.

**Who (policy):** **either party** (`view`). Otherwise `403`.

**Success `200`:** `data` is a **JSON array** of installment objects (not `{ items: ... }`). Ordered by `order`. Empty array for Individual requests.

**Errors:** `401`, `403`, `404`.

---

### 2.12 Pay installment (Company)

`POST /api/v1/guarantor/{guarantorRequest}/installments/{installment}/pay`

**Auth:** Sanctum bearer.

**Who (policy):** **counterparty** of this installment’s request, and request status is one of `accepted`, `in_progress`, `overdue`. Otherwise `403`.

**Body:** none (no extra fields).

**Charged amount:** that installment’s `amount` **only** (fees are not added).

**Order rule:** if `order > 1`, the previous installment (`order - 1`) must be `paid` or `released`. Else `422` `"Previous installment must be paid first"`.

**Already paid / not pending:** `422` `"This installment has already been paid"` (also if status is `released` / anything other than `pending`).

**Wrong request status:** `422` `"This status transition is not allowed"`.

**Success `200`:** same payment-initiation object as Individual pay (`url`, `driver`, `payable`, …). Open `url`. After gateway success:

- Installment becomes `paid`, `paid_at` set.
- If request was `accepted` or `overdue`, request becomes `in_progress` (and `overdue_at` cleared when recovering from overdue).
- Both parties receive **`GuarantorPaymentReceivedNotification`**.
- Paying installment N (N > 1) triggers **release** of the previous paid installment to the requester (they get an “installment released” notification).

**Ambiguous binding:** the installment UUID is not explicitly scoped to `{guarantorRequest}` in the route. Always pass matching IDs from show/list.

**Errors:** `401`, `403`, `404` (request or installment), `422` as above.

---

### 2.13 Chat — list conversations

`GET /api/v1/chats/guarantor`

**Auth:** Sanctum bearer.

**Who:** authenticated user; returns guarantor conversations they participate in.

**Query:** `per_page` (default **15**).

**Success `200`:**

```json
{
  "items": [
    {
      "id": "9f3c2a1b-0000-4000-8000-000000000050",
      "requester": { "id": 10, "name": "Provider Co", "type": "user", "image": null },
      "counterparty": { "id": 20, "name": "Ahmed Mohamed", "type": "user", "image": null },
      "last_message": { "content": "Hello", "created_at": "2026-08-15T12:00:00+00:00" },
      "last_message_at": "2026-08-15T12:00:00+00:00",
      "guarantor_request": {
        "id": "9f3c2a1b-0000-4000-8000-000000000001",
        "title": "Construction",
        "status": { "value": "accepted", "label": "Accepted", "color": "#8b5cf6" },
        "type": { "value": "company", "label": "Company", "color": "#8b5cf6" }
      }
    }
  ],
  "paginate": {
    "total": 5,
    "count": 5,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

`last_message` / `guarantor_request` appear when those relations are loaded. **There is no `unread_count` field** on this resource (ignore outdated examples that mention it).

---

### 2.14 Chat — open or get conversation

`POST /api/v1/chats/guarantor`

**Auth:** Sanctum bearer.

**Who (policy `chat`):** **either party** and request status is **`accepted`**, **`in_progress`**, or **`overdue`**. Otherwise `403` `"This action is unauthorized."`

A second guard can return `422` `"Chat is only available after the request is accepted"` if status is not those three (policy should already block).

**Body:**

| Field | Type | Required | Rules |
|---|---|---|---|
| `guarantor_request_id` | UUID | yes | must exist on `guarantor_requests` |

**Success `200`:** one conversation object (same fields as list item, typically with both participants).

Idempotent: calling again returns the existing conversation. Accept already creates one.

**Errors:** `401`, `403`, `422` validation (`guarantor_request_id`), `422` chat not allowed, `404` if id does not exist (`exists` rule → 422 `"The selected guarantor request id is invalid."` rather than 404).

---

### 2.15 Chat — list messages

`GET /api/v1/chats/guarantor/{conversation}`

**Auth:** Sanctum bearer.

**Who:** conversation **participant** only (`403` otherwise).

**Query:** `per_page` (default **20**).

**Success `200`:** `{ "items": [ message, ... ], "paginate": { ..., "next_page_url", "prev_page_url", ... } }`.

**Message object:**

```json
{
  "id": "9f3c2a1b-0000-4000-8000-000000000060",
  "conversation_id": "9f3c2a1b-0000-4000-8000-000000000050",
  "content": "Please confirm the site visit",
  "sender": {
    "id": 20,
    "socket_id": "user-20",
    "type": "user",
    "name": "Ahmed Mohamed",
    "online": false,
    "image": "https://..."
  },
  "attachments": [],
  "read_at": null,
  "created_at": "2 hours ago",
  "created_at_iso": "2026-08-15T10:00:00+00:00"
}
```

| Field | Notes |
|---|---|
| `created_at` | **Humanized** string, not ISO |
| `created_at_iso` | ISO-8601 — use this for sorting |
| `attachments` | Only if media was loaded; each item is media shape **plus** `available` (boolean). Missing files: `available: false`, empty `url` / `file_name`, optional `label` |
| `last_attachment` | Only present when there is at least one attachment payload |

**Errors:** `401`, `403`, `404`.

---

### 2.16 Chat — send message

`POST /api/v1/chats/guarantor/{conversation}/send`

**Auth:** Sanctum bearer.

**Who:** conversation **participant**.

**Content-Type:** `multipart/form-data` if attaching files.

**Body:**

| Field | Type | Required | Rules |
|---|---|---|---|
| `content` | string | required unless `files` is present | max 5000; nullable |
| `files` | file[] | required unless `content` is present | each jpg/jpeg/png/pdf/**webp**, max 5120 KB |

Must send **at least one** of `content` or `files`.

**Success `200`:** `data` = one message object.

**Errors:** `401`, `403`, `422` validation.

---

## 3. Status reference

### Request statuses (`status.value`)

English labels below are the `en` translations. Colors are server hex values.

| Value | Label | Color | Meaning | Terminal? |
|---|---|---|---|---|
| `new` | New | `#6b7280` | Exists on the enum **only**. **Create never uses it.** Treat as unused. | no |
| `pending_admin` | Pending Admin Review | `#f59e0b` | Just created. Waiting for Dashboard review. Requester may edit/delete/media. Counterparty should not treat this as “your turn”. | no |
| `approved_by_admin` | Approved by Admin | `#3b82f6` | Admin approved. **Counterparty’s turn** to accept or reject. No pay, no chat yet. | no |
| `rejected_by_admin` | Rejected by Admin | `#ef4444` | Admin rejected. Dead. | **yes** |
| `accepted` | Accepted | `#8b5cf6` | Counterparty accepted. Chat opens. Individual: waiting for **full pay**. Company: waiting for **installment 1** (and later ones); first successful installment pay moves request to `in_progress`. | no |
| `rejected` | Rejected | `#f97316` | Counterparty rejected. Dead. | **yes** |
| `in_progress` | In Progress | `#06b6d4` | Individual: payment captured. Company: active after first installment payment (or overdue recovery). Work / escrow active. Either party may **end** or **open dispute**. | no |
| `overdue` | Overdue | `#ef4444` | Company: a pending installment is ≥ 3 days past due (daily job). Individual does not use this path. Either party may **end** or **open dispute**. Counterparty may still **pay** the overdue installment. | no |
| `disputed` | Disputed | `#dc2626` | A party opened a dispute. End and further payments frozen; chat remains. Admin resolves from Dashboard. | no |
| `ended` | Ended | `#10b981` | Closed successfully; funds released per type. | **yes** |
| `ended_via_dispute` | Ended (via dispute) | `#10b981` | Admin resolved dispute — full release to one party (requester). Distinct provenance from plain `ended`. | **yes** |
| `cancelled` | Cancelled | `#6b7280` | Admin cancelled (Dashboard). Mobile cannot set this. | **yes** |
| `cancelled_via_dispute` | Cancelled (via dispute) | `#6b7280` | Admin resolved dispute — full release to counterparty. | **yes** |
| `escalated` | Escalated | `#7c3aed` | Admin resolved dispute — escalated to platform (terminal). | **yes** |
| `settled` | Settled | `#0d9488` | Admin resolved dispute — percentage split (terminal). | **yes** |

Terminal statuses cannot be left by mobile parties.

### Mobile party transitions

Only these succeed. Anyone / anything else → `422` `"This status transition is not allowed"`. Repeating the current status → `"The request is already in this status"`.

| Actor | From | To | How |
|---|---|---|---|
| Counterparty | `approved_by_admin` | `accepted` | `POST .../status` `{ "status": "accepted" }` |
| Counterparty | `approved_by_admin` | `rejected` | `POST .../status` `{ "status": "rejected", "reason": "..." }` |
| Counterparty | `in_progress` | `ended` | `POST .../status` `{ "status": "ended" }` |
| Counterparty | `overdue` | `ended` | same |
| Requester | `in_progress` | `ended` | same |
| Requester | `overdue` | `ended` | same |
| Either party | `in_progress` or `overdue` | `disputed` | `POST .../dispute` `{ "reason": "..." }` |

**Not allowed for mobile (admin / system / gateway):**

| To | Caused by |
|---|---|
| `pending_admin` | Create |
| `approved_by_admin` | Admin Dashboard |
| `rejected_by_admin` | Admin Dashboard |
| `in_progress` | Individual: payment gateway success. Company: first installment payment from `accepted`, or overdue recovery after a late installment is paid. |
| `overdue` | Daily overdue job (company installments) |
| `disputed` | `POST .../dispute` from either party while `in_progress` or `overdue` |
| `cancelled` | Admin Dashboard |
| `ended_via_dispute` / `cancelled_via_dispute` / `escalated` / `settled` | Admin dispute resolution (Dashboard) |
| `ended` from `accepted` | **Blocked** even if some company installments are already paid |

Admin (Dashboard) is allowed any transition; that is not this API.

### Installment statuses (`installments[].status.value`)

| Value | Label | Color | Meaning | Who sets it |
|---|---|---|---|---|
| `pending` | Pending | `#f59e0b` | Not paid yet. Default on create. **Stays `pending` even when the due date has passed** — use `is_past_due` and/or request `overdue`. | Create |
| `paid` | Paid | `#3b82f6` | Gateway confirmed this installment. Money is held. | Payment callback (counterparty paid) |
| `released` | Released | `#10b981` | Funds moved to requester (previous installment when the next is paid; last paid installment when request is ended; or auto-release 14 days after due **if already `paid`**). | System / end — **no mobile release endpoint** |
| `overdue` | Overdue | `#ef4444` | **Defined but not written** by current jobs. Do not build UI that waits for this value. | — |

---

## 4. The full lifecycle, step by step

Shared for both types unless noted. “User app” = counterparty. “Create app” = requester (do not ship create on the User app).

### Individual

| Status | User (counterparty) screen | Requester screen | Enabled actions |
|---|---|---|---|
| `pending_admin` | Optional: “waiting for review” if they opened a deep link. **No accept, no pay, no chat.** | “Submitted — waiting for admin.” Edit / delete / replace files OK. | Update, delete, delete media (requester). Show/list for both once they can see it. |
| `approved_by_admin` | **Decision screen:** Accept or Reject (reject requires reason). | “Waiting for the other party.” No pay, no chat. | Counterparty: status `accepted` / `rejected`. |
| `rejected_by_admin` | Closed. | Closed — “rejected by admin”. | None (terminal). |
| `accepted` | **Pay full `total`.** Chat **on**. | Waiting for payment. Chat on. | Counterparty: `POST .../pay`. Both: chat. **End is disabled.** |
| `rejected` | Closed. | Closed — they rejected. | None. |
| `in_progress` | Work in progress. Chat on. Can **End** or **Dispute**. Pay button off (already paid for Individual). | Same. Can **End** or **Dispute**. | Both: status `ended` or dispute endpoint, chat. |
| `disputed` | Dispute open — End and pay frozen. Chat on. Await admin resolution. | Same. | Show only; no End/pay. |
| `ended` / `ended_via_dispute` / `cancelled` / `cancelled_via_dispute` / `escalated` / `settled` | Closed / summary. Chat policy **off** (only `accepted` \| `in_progress` \| `overdue`). Existing conversation GET/send still allowed **if they are participants** — gate **opening** the guarantor chat UI on request status, not on leftover messages. | Same. | Show only. |

There is no Individual installment list to pay. `overdue` is not part of the Individual happy path.

### Company

Same as Individual through `accepted`, then:

| Status | User (counterparty) screen | Requester screen | Enabled actions |
|---|---|---|---|
| `accepted` | **Installment schedule.** Enable Pay only on the first `pending` installment whose previous is `paid` or `released` (or order 1). Chat **on**. **End is disabled** (cannot end from `accepted`). After paying #1, request moves to **`in_progress`**. | Chat on. Watch installments move `pending` → `paid` → `released`. No pay. | Counterparty: installment pay. Both: chat. |
| `in_progress` | Same installment UI. End **enabled**. Dispute **enabled**. | End enabled. Dispute enabled. | Pay remaining pending (in order), end, dispute, chat. |
| `overdue` | Banner: overdue. `is_past_due` true on the unpaid due installment. Pay that installment (still in sequence). End enabled. Chat on. | Banner + End. | Same as in_progress. |
| Past due but request still `accepted` | Days 1–2 after due: reminder may have been sent; request status **unchanged**. Show `is_past_due` on the installment. | Same. | Pay still allowed. |
| `ended` | Closed. Last `paid` installment is released if one existed. | Funds for that installment released (minus fee share). | Show only. |

Admin may leave the request in `pending_admin` for a long time if they do not act — **not a mobile bug**. Admins with `manage guarantors` **are** notified on create; a long wait still means Dashboard review, not a missing mobile push.

---

## 5. Company installments in detail

### Pay order

1. Only the counterparty pays.
2. Only `pending` installments can be paid.
3. Installment `N` requires `N-1` to be `paid` or `released`.
4. Charge = that row’s `amount` (not `total`, not + fees).

Disable Pay on future rows in the UI even before the API 422s.

### What `in_progress` means (Company)

- Paying installment 1 from `accepted` **does** set the request to `in_progress` (same gateway callback as Individual).
- If the request was already `overdue`, a successful installment payment also clears `overdue_at`.
- Check installment statuses for pay/release UI; request status alone is not enough.

### Overdue timeline (Company, daily job at 00:00)

Jobs run on **pending** installments whose `due_date` is already before “now”, on requests that are not terminal and not `disputed`.

Calendar math uses whole days between the due date (start of day) and today (start of day):

| Days after due date | What the server does | What the UI should show |
|---|---|---|
| **0 (due date)** | Job may run but takes **no** notify/overdue action (`days < 1`). | Neutral / due today. Status still `pending`. `is_past_due` becomes true once the due date is in the past. |
| **1–2** | **Reminder** to the **counterparty only** (`installment_due`). Request status unchanged. | “Payment due / reminder”. Keep Pay enabled. |
| **≥ 3 and &lt; 14** | Request → `overdue` (if not already), `overdue_at` set. **Both** parties get `installment_overdue`. Installment row **stays `pending`**. | Request badge Overdue. Installment still Pending + `is_past_due: true`. Pay still allowed. End allowed. |
| **≥ 14** | If installment is **`paid`**: auto-release to requester (status → `released`), requester notified `installment_released`. If still **`pending` (unpaid)**: **one-time admin escalation** (`UnpaidOverdueInstallmentEscalationNotification` to admins with `show guarantors`); sets `escalated_at`. **No** status or wallet mutation. | Paid-and-held money can auto-release; unpaid rows show `escalated_at` on the installment for Dashboard visibility. |

The due/overdue job can run **every day** on the same pending row (it does not skip already-notified rows). Expect possible repeat reminders.

`overdue_notified_at` on the installment is updated when a due or overdue notification is sent. `escalated_at` is set once when an unpaid installment is escalated past day 14.

---

## 6. Chat

### When it becomes available

Chat for a request is allowed only when status is:

- `accepted`
- `in_progress`
- `overdue`

**Not** during `pending_admin` or `approved_by_admin`. Accepting is what opens it (the server creates the conversation on accept). Opening via `POST /api/v1/chats/guarantor` before that is `403`.

Gate the chat entry point on **`status.value`** from show/list. Do not offer chat on the approval-waiting screen.

### How to get the conversation id

The guarantor **show payload does not include** `conversation_id`. After `accepted`:

1. `POST /api/v1/chats/guarantor` with `{ "guarantor_request_id": "<uuid>" }`, or
2. `GET /api/v1/chats/guarantor` and match `guarantor_request.id`.

### Realtime

There is **no dedicated “guarantor chat” broadcast channel name**. Use the generic conversation channel:

- Subscribe: private channel **`chats.{conversationId}`** (Echo: `Echo.private('chats.' + id)`).
- Authorized if the user is a participant (`user1` / `user2`) of that conversation. Admins can join from Dashboard; mobile Users only if they are a party.

Domain notifications (accept, overdue, etc.) broadcast on the user’s personal channel **`user-{numericUserId}`** (see `docs/mobile/AUTH_FLOW.md` / existing notification wiring). Firebase is separate (below).

After `ended` / `cancelled` / `rejected*`, **do not** offer “Open guarantor chat” from the request (policy `chat` fails). A leftover conversation may still list/send for participants; product UI should still hide chat when request status is not the three allowed values.

---

## 7. Notifications the mobile app should expect

Channels for Guarantor notifications:

- **In-app / database** — always (User and Provider notifiables for party events; Admin for pending-review).
- **Broadcast (websocket)** — always.
- **Firebase push** — **User and Provider** for party events (`GuarantorFirebaseNotifiable`); **Admin** for pending-review and unpaid-overdue escalation notifications.

Firebase `data` is a **subset** of the database payload (usually ids only). Database records use `title_translated_key` / `body_translated_key` (keys below) plus extra fields.

English title/body (keys resolve via `Accept-Language`):

| Event | Title | Body | Who receives it | Push (User)? | Extra payload |
|---|---|---|---|---|---|
| Request created | Guarantor Request Submitted | Your guarantor request has been submitted and is pending admin review | **Requester only** | yes if User | `guarantor_request_id`, `type`. FCM data: `guarantor_request_id` only. Broadcast type: `guarantor created` |
| New request needs admin review | New guarantor request | A guarantor request is awaiting your review. | **Admins** with `manage guarantors` (`GuarantorPendingReviewNotification`) | yes if **Admin** (Firebase enabled; not User/Provider) | `guarantor_request_id`, `type`. FCM data: `guarantor_request_id`, `screen: 'guarantor'`. Broadcast type: `guarantor pending review` |
| Admin approved | Guarantor Request Approved by Admin | …approved by admin and is waiting for counterparty response | **Requester and counterparty** | yes if User | same shape. Type: `guarantor admin approved` |
| Admin rejected | Guarantor Request Rejected by Admin | Your guarantor request has been rejected by admin | **Requester only** | yes if User | Type: `guarantor admin rejected` |
| Counterparty accepted | Guarantor Request Accepted | The counterparty has accepted your guarantor request | **Requester only** | yes if User | Type: `guarantor accepted` |
| Counterparty rejected | Guarantor Request Rejected | The counterparty has rejected your guarantor request | **Requester only** | yes if User | Type: `guarantor counterparty rejected` |
| Ended | Guarantor Request Ended | Your guarantor request has been ended | **Both parties** (`GuarantorEndedNotification`) | yes if User | `guarantor_request_id`, `type`, `final_status` (`ended`). FCM: `guarantor_request_id`, `final_status`. Type: `guarantor ended` |
| Cancelled (admin) | Guarantor Request Cancelled | Your guarantor request has been cancelled | **Both parties** (`GuarantorCancelledNotification`) | yes if User | `guarantor_request_id`, `type`, `cancellation_reason`. FCM: `guarantor_request_id`, `cancellation_reason`. Type: `guarantor cancelled` |
| Installment due (day 1–2) | Installment Payment Due | An installment payment is due for your guarantor request | **Counterparty only** | yes if User | `guarantor_request_id`, `installment_id`, `installment_order`, `amount`, `due_date`. FCM: request + installment ids. Type: `installment due` |
| Installment overdue (day ≥ 3) | Installment Payment Overdue | An installment payment is overdue for your guarantor request | **Both parties** | yes if User/Provider | same as due. Type: `installment overdue` |
| Payment captured / gateway success | Guarantor Payment Received | A payment has been received for your guarantor request | **Both parties** (`GuarantorPaymentReceivedNotification`) | yes if User/Provider | `guarantor_request_id`, `type`, `payment_id`, `amount`; installment ids when applicable. Type: `guarantor payment received` |
| Dispute opened | Guarantor dispute opened | A dispute has been opened on a guarantor request and needs review. | **Other party** + **Admins** with `manage guarantors` | yes if User/Provider/Admin | `guarantor_request_id`, `reason`, `final_status: disputed`. Type: `guarantor disputed` |
| Installment released | Installment Payment Released | An installment payment has been released to your account | **Requester only** | yes if User/Provider | includes `released_at`. Type: `installment released` |
| Unpaid installment ≥ 14 days (Dashboard) | Unpaid overdue installment | Installment #N (amount X) is still unpaid 14+ days past due… | **Admins** with `show guarantors` | yes if Admin | `guarantor_request_id`, `installment_id`, `installment_order`, `amount`, `due_date`. Type: `unpaid overdue installment escalation` |

### Events that do **not** send a notification today

Do **not** build UI that waits for a push for these:

| Event | Reality |
|---|---|
| **Counterparty: “a request was created naming you”** | Counterparty is **not** notified at create. They first hear at **admin approved** (if they are a User, via push). |
| **Counterparty: “you accepted”** | No self-notification on accept. |
| **Individual pay button / installment pay initiated** | Initiation is not a notification; only the payment `url` response. |
| **Requester edited / deleted a pending request** | No notification. |
| **Chat message** | Uses the **chat** realtime channel, not this Guarantor notification list. |
| **Refund completed to card** | Not implemented (see limitations). |

Unused translation keys exist (`guarantor_approved` / `guarantor_has_been_approved`, `guarantor_rejected` / `guarantor_has_been_rejected`) that current Guarantor notifications **do not** send. Do not listen for those keys for this feature.

---

## 8. Field reference: `authorization_type`

**Arabic context:** توكيل / وكالة.

| Allowed values | English label |
|---|---|
| `power_of_attorney` | Power of Attorney |
| `agency` | Agency |

This is **company signatory KYC metadata**, collected **once** on `POST /company`. It is stored on `company_detail.authorization_type` and returned as `{ value, label }` (no color).

**It has no effect on who may pay.**

- Payment is **always** the **counterparty** (the User whose phone was entered).
- Individual pay and installment pay policies ignore `authorization_type`.
- Choosing `agency` vs `power_of_attorney` does **not** grant the requester (or anyone else) permission to pay.

Update endpoint cannot change this field after create.

---

## 9. Known current limitations

Be explicit with QA and with UI copy:

1. **No card/gateway refund.** Admin cancel reverses **internal wallet holds** only. The original charge is **not** automatically refunded to the payer’s card. Do not show “you will be refunded to your card” unless a later API exists.

2. **Create is not User-app product.** Create endpoints have no requester-role check, and Sanctum tokens are User tokens today — a User app *could* call create. **Do not ship a create flow on the User app.** The User is the counterparty / payer.

3. **Payment-received push exists.** After checkout, expect `GuarantorPaymentReceivedNotification` to both parties when the gateway callback succeeds. Individual: also expect `in_progress`. Company: installment `paid`; request typically `in_progress` after first pay.

4. **Parties cannot cancel** via `POST .../status` with `cancelled` (422). Only admin can cancel. Cancel notifies both parties with `GuarantorCancelledNotification` (not the Ended notification).

5. **Company cannot be ended from `accepted`**, even if installments are already paid. End is only from `in_progress` or `overdue`.

6. **Installment `overdue` status value is unused.** Drive overdue UI from request `overdue` + installment `is_past_due` / `escalated_at`.

7. **Status `new` is unused.** Create starts at `pending_admin`. Ignore examples that show `"status": "new"` after create.

8. **Chat id is not on the guarantor resource.** Use the chat endpoints.

9. **Fees are not client-controlled** (`10.00`). Individual checkout charges `total`; company installment checkout charges the installment `amount` only.

10. **Company create has no description field**; show will have `description: ""`. Use `project_type` / `title` for headings.

---

## Quick UI checklist (User / counterparty app)

| Request status | Show chat | Accept/Reject | Pay Individual | Pay installment | End | Dispute |
|---|---|---|---|---|---|---|
| `pending_admin` | no | no | no | no | no | no |
| `approved_by_admin` | no | **yes** | no | no | no | no |
| `accepted` | **yes** | no | **yes** (Individual) | **yes** (Company, in order) | no | no |
| `in_progress` | **yes** | no | no | **yes** if pending remain | **yes** | **yes** |
| `overdue` | **yes** | no | no | **yes** | **yes** | **yes** |
| `disputed` | **yes** | no | no | no | no | no |
| terminal (`rejected*`, `ended*`, `cancelled*`, `escalated`, `settled`) | no (entry) | no | no | no | no | no |
