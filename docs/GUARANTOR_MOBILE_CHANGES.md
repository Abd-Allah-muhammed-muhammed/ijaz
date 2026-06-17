# Guarantor Module — Mobile Developer Changes

> Branch: feat/guarantor-module
> Last updated: 2026-06-16
> Note: This file can be used directly as a prompt for AI assistants
>       to implement changes on the mobile side.

---

## Overview

The Guarantor module replaces the old Guarantee Request feature.
New base URL: `/api/v1/guarantor`
Old base URL: `/api/v1/guarantee-requests` (still works — not removed yet)

---

## New Endpoints

### Individual Guarantor

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/guarantor` | sanctum | List my guarantors (as requester or counterparty) |
| POST | `/api/v1/guarantor/individual` | sanctum | Create individual guarantor request |
| GET | `/api/v1/guarantor/{id}` | sanctum | Show guarantor details |
| POST | `/api/v1/guarantor/{id}` | sanctum | Update guarantor (status=new only) |
| DELETE | `/api/v1/guarantor/{id}` | sanctum | Delete guarantor (status=new only) |
| POST | `/api/v1/guarantor/{id}/status` | sanctum | Update status (approve/reject/cancel/end) |
| POST | `/api/v1/guarantor/{id}/pay` | sanctum | Pay (individual — counterparty only) |
| DELETE | `/api/v1/guarantor/{id}/media/{uuid}` | sanctum | Delete media |

### Company Guarantor

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/guarantor/company` | sanctum | Create company guarantor request |
| GET | `/api/v1/guarantor/{id}/installments` | sanctum | List installments |
| POST | `/api/v1/guarantor/{id}/installments/{installment}/pay` | sanctum | Pay an installment |

### Chat

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/chats/guarantor` | sanctum | List guarantor chats |
| POST | `/api/v1/chats/guarantor` | sanctum | Open chat (status must be approved+) |
| GET | `/api/v1/chats/guarantor/{conversation}` | sanctum | List messages |
| POST | `/api/v1/chats/guarantor/{conversation}/send` | sanctum | Send message |

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
  "status": "approved",
  "reason": "سبب الرفض أو الإلغاء (required if rejected/cancelled)"
}
```

---

## Response Shapes

### GuarantorResource
```json
{
  "id": "uuid",
  "type": { "value": "individual", "label": "أفراد", "color": "#..." },
  "status": { "value": "new", "label": "جديد", "color": "#22c55e" },
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

## Status Values

### Guarantor Status
| Value | Label (ar) | Who triggers |
|-------|-----------|--------------|
| `new` | جديد | System (on create) |
| `approved` | موافق عليه | Counterparty |
| `rejected` | مرفوض | Counterparty |
| `in_progress` | جاري التنفيذ | System (after payment) |
| `overdue` | متأخر | System (scheduler) |
| `ended` | منتهي | Requester or Counterparty |
| `cancelled` | ملغي | Requester, Counterparty, or Admin |
| `refunded` | مسترد | Admin |

### Installment Status
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

## Old Endpoints (still active — not removed yet)
```
/api/v1/guarantee-requests/* → still works
```
Will be removed after mobile migration is complete.
Notify team before removal.

---

## Changelog
<!-- Updated after each phase -->
| Date | Change | Phase |
|------|--------|-------|
| TBD | Initial module created | Phase 1 |
