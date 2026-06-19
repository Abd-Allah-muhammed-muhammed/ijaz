# Guarantor Module — Mobile Developer Changes

> Branch: feature/guarantor-module  
> Last updated: 2026-06-19  
> Status: LIVE on production  
> Note: This file can be used directly as a prompt for AI assistants
>       to implement changes on the mobile side.

---

## Overview

The Guarantor module **replaces** the old Guarantee Request feature completely.

⚠️ The old `/api/v1/guarantee-requests/*` endpoints have been **permanently removed**.
Do NOT use them — they will return 404.

New base URL: `/api/v1/guarantor`  
Chat URL: `/api/v1/chats/guarantor`

---

## New Endpoints

### Individual Guarantor

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/guarantor` | sanctum | List my guarantors (as requester or counterparty) |
| POST | `/api/v1/guarantor/individual` | sanctum | Create individual guarantor request |
| GET | `/api/v1/guarantor/{id}` | sanctum | Show guarantor details |
| POST | `/api/v1/guarantor/{id}` | sanctum | Update guarantor (`status = pending_admin` only) |
| DELETE | `/api/v1/guarantor/{id}` | sanctum | Delete guarantor (`status = pending_admin` only) |
| POST | `/api/v1/guarantor/{id}/status` | sanctum | Update status (accept/reject/end — see Status Flow) |
| POST | `/api/v1/guarantor/{id}/pay` | sanctum | Pay (individual — counterparty only, `status = accepted`) |
| DELETE | `/api/v1/guarantor/{id}/media/{uuid}` | sanctum | Delete media (`status = pending_admin` only) |

### Company Guarantor

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/guarantor/company` | sanctum | Create company guarantor request |
| GET | `/api/v1/guarantor/{id}/installments` | sanctum | List installments |
| POST | `/api/v1/guarantor/{id}/installments/{installment}/pay` | sanctum | Pay an installment (`status = accepted` or `in_progress`) |

### Chat

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats/guarantor` | sanctum | List guarantor chats |
| POST | `/api/v1/chats/guarantor` | sanctum | Open chat (status must be `in_progress` or `overdue`) |
| GET | `/api/v1/chats/guarantor/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/guarantor/{conversation}/send` | sanctum | Send message |

> ⚠️ Chat opens **automatically** when payment is confirmed and status becomes `in_progress`.
> No need to call `POST /chats/guarantor` manually — it's auto-created.
> You can call it to get the conversation ID if needed (idempotent).

---

## Request Bodies

### POST `/api/v1/guarantor/individual`
```json
{
  "counterparty_phone": "05xxxxxxxx",
  "amount": 5000,
  "title": "عنوان الطلب",
  "description": "تفاصيل الطلب",
  "signature": "[file: jpg/jpeg/png/pdf, max 5MB]"
}
```

### POST `/api/v1/guarantor/company`

> Note: `project_type` is **required** for company type (not nullable).

```json
{
  "counterparty_phone": "05xxxxxxxx",
  "project_type": "مقاولات",
  "total_amount": 30000,
  "installments": [
    { "order": 1, "amount": 10000, "due_date": "2026-05-01" },
    { "order": 2, "amount": 20000, "due_date": "2026-07-01" }
  ],
  "company_name": "اسم الشركة",
  "commercial_register": "رقم السجل",
  "region_id": 1,
  "city_id": 5,
  "authorized_name": "اسم المفوض",
  "authorized_id_number": "رقم الهوية",
  "authorization_type": "power_of_attorney",
  "requester_account_holder": "اسم صاحب الحساب",
  "requester_iban": "SA...",
  "counterparty_account_holder": "اسم صاحب الحساب",
  "counterparty_iban": "SA...",
  "signature": "[file]",
  "authorized_id": "[file]",
  "contracts": ["[file]", "[file]"],
  "company_documents": ["[file]"]
}
```

### POST `/api/v1/guarantor/{id}/status`
```json
{
  "status": "accepted",
  "reason": "سبب الرفض (required if rejected)",
  "notes": "ملاحظات اختيارية"
}
```

Allowed `status` values depend on actor and current state — see **Status Flow** below.
Common counterparty values: `accepted`, `rejected`.  
Common party values (when allowed): `ended`.

---

## Response Shapes

### GuarantorResource
```json
{
  "id": "uuid",
  "type": { "value": "individual", "label": "أفراد", "color": "#..." },
  "status": { "value": "pending_admin", "label": "بانتظار مراجعة الإدارة", "color": "#..." },
  "title": "عنوان الطلب",
  "description": "التفاصيل",
  "amount": 5000.00,
  "fees": 10.00,
  "total": 5010.00,
  "project_type": null,
  "requester": { "id": "...", "name": "...", "type": "user" },
  "counterparty": { "id": "...", "name": "...", "type": "user" },
  "installments": [],
  "company_detail": null,
  "status_histories": [],
  "media": [],
  "created_at": "2026-06-01T10:00:00+00:00"
}
```

### InstallmentResource
```json
{
  "id": "uuid",
  "order": 1,
  "amount": 10000.00,
  "due_date": "2026-05-01",
  "paid_at": null,
  "released_at": null,
  "status": { "value": "pending", "label": "قيد الانتظار", "color": "#..." }
}
```

---

## Media

All media returned in `media` array with `collection_name` field:

| collection_name | Description |
|----------------|-------------|
| `signature` | Requester signature (single file) |
| `files` | Additional attachments |

Filter by `collection_name` to find specific files:
```json
{
  "media": [
    { "id": "uuid", "collection_name": "signature", "url": "...", "mime_type": "image/png" },
    { "id": "uuid", "collection_name": "files", "url": "...", "mime_type": "application/pdf" }
  ]
}
```

> Company-type requests also attach documents to `company_detail` collections:
> `authorized_id`, `contracts`, `iban_certificates`, `company_documents`.

---

## Status Flow

```
Create → pending_admin → approved_by_admin → accepted → [PAY] → in_progress → ended
                      ↓                  ↓
               rejected_by_admin       rejected

Admin only at any time: cancelled, refunded
```

### Who does what:
| Action | Who |
|--------|-----|
| Create request | Requester |
| Approve / Reject / Cancel | Admin only (via dashboard) |
| Accept / Reject | Counterparty (after admin approves) |
| Pay | Counterparty (when status = `accepted`) |
| End | Requester OR Counterparty (when `in_progress` / `overdue`) |
| Delete | Requester (only when `pending_admin`) |

> ⚠️ Neither requester nor counterparty can cancel — Admin only.
> Requester can only DELETE the request while status = `pending_admin`.

### Guarantor Status Values

| Value | Label (ar) | Who triggers |
|-------|-----------|--------------|
| `pending_admin` | بانتظار مراجعة الإدارة | System (on create) |
| `approved_by_admin` | موافق عليه من الإدارة | Admin |
| `rejected_by_admin` | مرفوض من الإدارة | Admin (terminal) |
| `accepted` | مقبول | Counterparty |
| `rejected` | مرفوض | Counterparty (terminal) |
| `in_progress` | جاري التنفيذ | System (after payment) |
| `overdue` | متأخر | System (scheduler) |
| `ended` | منتهي | Requester or Counterparty |
| `cancelled` | ملغي | Admin only |
| `refunded` | مسترد | Admin |

- Chat opens on: `in_progress` (after payment — NOT on `accepted`)
- Pay allowed on: `accepted` only (company installments also allowed from `in_progress`)

### Installment Status Values

| Value | Label (ar) |
|-------|-----------|
| `pending` | قيد الانتظار |
| `paid` | مدفوع |
| `released` | محرر |
| `overdue` | متأخر |
| `refunded` | مسترد |

---

## Authorization Type Values

| Value | Label (ar) |
|-------|-----------|
| `power_of_attorney` | تفويض |
| `agency` | وكالة |

---

## Error Responses

All errors follow this shape:
```json
{
  "success": false,
  "message": "رسالة الخطأ",
  "data": [],
  "errors": {}
}
```

Common status codes:
- `401` — غير مسجل دخول
- `403` — غير مصرح
- `404` — غير موجود
- `422` — بيانات غير صحيحة

---

## Breaking Changes

### Old guarantee-requests API — REMOVED ❌
```
/api/v1/guarantee-requests/*  → 404 NOT FOUND (removed)
/api/v1/chats/guarantee/*     → 404 NOT FOUND (removed)
```

### Migration guide:

| Old | New |
|-----|-----|
| `GET /api/v1/guarantee-requests` | `GET /api/v1/guarantor` |
| `POST /api/v1/guarantee-requests` | `POST /api/v1/guarantor/individual` or `/company` |
| `PUT /api/v1/guarantee-requests/{id}/status` | `POST /api/v1/guarantor/{id}/status` |
| `POST /api/v1/guarantee-requests/{id}/pay` | `POST /api/v1/guarantor/{id}/pay` (individual) or `POST /api/v1/guarantor/{id}/installments/{inst}/pay` (company) |
| `GET /api/v1/chats/guarantee` | `GET /api/v1/chats/guarantor` |
| `POST /api/v1/chats/guarantee` | `POST /api/v1/chats/guarantor` |
| `status: "new"` (string) | `status: { value, label, color }` (object) |
| `status: "approved"` | Split into `approved_by_admin` + `accepted` |
| `user` / `provider` keys | `requester` / `counterparty` keys |
| No type distinction | `type: { value: "individual" \| "company" }` |
| No signature required | `signature` file required on create |
| No installments | `installments[]` array for company type |
| Chat opens on approved | Chat opens on `in_progress` (after payment) |

### Key differences (summary)

1. `type` field added (`individual` / `company`) — legacy had no type distinction
2. `status` returns object `{ value, label, color }` not raw string
3. `requester` / `counterparty` replace `user` / `provider` naming
4. `installments` array required for company type with sum validation against `total_amount`
5. `signature` file required on create (individual and company) — stored in `signature` collection
6. Chat at `/api/v1/chats/guarantor` (not `/api/v1/chats/guarantee`)
7. Payment: individual uses `POST /api/v1/guarantor/{id}/pay`; company uses installment pay endpoint
8. Status transitions enforced via `GuarantorStatusEnum::isAllowed()` — terminal states cannot transition
9. Admin review step (`pending_admin` → `approved_by_admin`) before counterparty can accept
10. Response may include `rejected_at` timestamp when rejected

---

## Changelog

| Date | Change |
|------|--------|
| 2026-06-19 | Deployed to production — old guarantee-requests API removed |
| 2026-06-19 | Chat now opens on `in_progress` (after payment) not `accepted` |
| 2026-06-18 | Legacy GuaranteeRequest feature completely removed from codebase |
| 2026-06-17 | Status flow refactored — admin review step + `accepted` state added |
| 2026-06-17 | Supervisor configured — `ijaz-guarantor-worker` queue added |
| 2026-06-17 | Company installment pay allowed from `accepted` status |
| 2026-06-17 | Signature moved to dedicated `signature` media collection |
| 2026-06-17 | `project_type` made required for company type |
| 2026-06-16 | Guarantor module live — all 15 endpoints active |
| 2026-06-16 | Initial module created |
