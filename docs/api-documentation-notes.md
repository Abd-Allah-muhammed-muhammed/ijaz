# API Documentation Notes (Scramble + API v1)

This file complements generated docs at `/docs/api` and captures response variants that are implementation-specific.

Source of truth used while preparing these notes:

- `docs/API_INVENTORY.md`
- API controllers in `app/Http/Controllers/Api/V1`
- `app/Http/Controllers/Api/UserController.php`

## Authentication

- Protected endpoints require `Authorization: Bearer {token}`.
- Public endpoints are explicitly marked unauthenticated in docs for:
  - User auth login/register
  - Catalog endpoints
  - `GET /api/v1/property-advisements/all`
  - `GET /api/v1/car-advisements/all`

## Success Response Variants (Real Implementations)

### Auth

`POST /api/v1/user/auth/login`

- Success: token payload from `successResponseWithToken([], token)`
- Failure variants:
  - user not found (400)
  - account deleted/blocked/inactive (400)

`POST /api/v1/user/auth/register`

- Success: token + `UserResource`
- Failure: `something went wrong` (400)

### Wallet

`POST /api/v1/wallet/add-balance`

- Online payment success shape:

```json
{
  "status": "success",
  "transaction_id": "T-2026-000431",
  "driver": "paytabs",
  "url": "https://secure.paytabs.com/payment/page/...",
  "payable": true,
  "data": {
    "id": 98,
    "amount": 150.0,
    "payment_method": "online",
    "status": "pending"
  }
}
```

- Offline payment success shape:

```json
{
  "status": "pending",
  "transaction_id": "",
  "driver": "offline",
  "url": "",
  "payable": false,
  "data": {
    "id": 101,
    "amount": 200.0,
    "payment_method": "offline",
    "status": "pending"
  },
  "message": "top up request created successfully, waiting for admin approval"
}
```

`POST /api/v1/wallet/withdraw`

- Success shape:

```json
{
  "status": "pending",
  "data": {
    "id": 44,
    "amount": 75.0,
    "status": "pending"
  },
  "message": "Withdraw request created successfully and is pending admin approval."
}
```

- Failure variant:

```json
{
  "message": "You can't withdraw this amount."
}
```

### Orders and Guarantee Payments

`POST /api/v1/user/orders/{order}/{offer}/pay` and `POST /api/v1/guarantee-requests/{guaranteeRequest}/pay`

- Return payment-initiation payload from payment driver integration and may include gateway URL.
- Status is implementation-driven by payment gateway result.

## Error Response Notes (Observed)

Error shapes are not fully uniform across all endpoints. Docs preserve existing behavior as-is.

Commonly observed:

Validation errors (422):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

Authentication required (401):

```json
{
  "message": "Unauthenticated."
}
```

Authorization/ownership checks (403) in some endpoints:

```json
{
  "message": "forbidden !!"
}
```

Not found examples vary by endpoint, including custom messages such as:

- `not_found`
- `media not found`
- `Notification not found`

Server error fallback appears as:

- `something went wrong`

## Pagination Shapes

Multiple pagination formats are used in this codebase and are intentionally not unified.

Observed formats include:

- Laravel resource pagination with `meta` + `links`
- Custom wrapper style used by some collections with fields such as:
  - `items` / `data`
  - `total`
  - `per_page`
  - `current_page`
  - `last_page`
  - `count`

## Request Parameters

Request body, query, and path parameter extraction is generated from:

- FormRequest rules
- Inline `$request->...` access
- Route model binding

Examples in generated docs are enhanced through real-world values where available, and endpoint-level behavior should be cross-checked against `docs/API_INVENTORY.md` for business-specific edge cases.
