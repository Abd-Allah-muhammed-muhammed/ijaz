# Mobile Auth Flow Contract

This document describes changes to the login / register / OTP verification flow.
Update your app to match this contract exactly.

Base URL prefix: `/api/v1`

All successful and most error responses use this envelope:

```json
{
  "success": true,
  "data": {},
  "errors": {},
  "message": "",
  "token": ""
}
```

Empty `errors` is an object: `{}`.

---

## What changed

- Login and register **no longer return a usable access token**. They return a `verification_id` challenge instead.
- You **must** complete OTP verification (`POST /otp/verify`) before calling any authenticated API.
- Successful verification returns `access_token` (and the same value in the envelope `token` field). Store that and send it as `Authorization: Bearer …` on protected requests.
- A dedicated **`POST /otp/resend`** endpoint reuses the same `verification_id` (subject to cooldown).
- Login and register success payloads are **structurally identical** (same `data` keys).

---

## Client implementation flow

1. Call **register** or **login**.
2. Persist `data.verification_id`.
3. Show the OTP screen. Use `data.resend_available_at` to know when Resend may be enabled; use `data.expires_in` (seconds) for session countdown.
4. User enters the SMS code → call **`POST /otp/verify`** with `verification_id` + `code`.
5. On success, store `data.access_token` (or envelope `token` — same string).
6. Call protected APIs with:

   ```http
   Authorization: Bearer {access_token}
   ```

7. If verify returns `verification_expired` or the countdown hits zero → restart from login/register.
8. If verify returns `invalid_code` → show the error and remaining attempts from `data.attempts_remaining`.
9. Optional Resend: `POST /otp/resend` with the same `verification_id` after `resend_available_at`.

---

## Endpoints

### 1. Register

`POST /api/v1/user/auth/register`  
**Auth:** none  
**Content-Type:** `multipart/form-data` (required because of `image`)

#### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `f_name` | string | yes | max 255 |
| `l_name` | string | yes | max 255 |
| `email` | string (email) | yes | must be unique |
| `phone` | string | yes | valid phone; must be unique |
| `nationality_id` | integer | yes | must exist |
| `image` | file (image) | yes | max 2048 KB |
| `latitude` | string/number | yes | |
| `longitude` | string/number | yes | |
| `password` | string | no | optional; not used for OTP login |

#### Success — `200`

```json
{
  "success": true,
  "data": {
    "verification_id": "9f3c2a1b-0000-4000-8000-000000000001",
    "expires_in": 900,
    "resend_available_at": "2026-07-28T21:16:00+00:00"
  },
  "errors": {},
  "message": "",
  "token": ""
}
```

| Field | Meaning |
|---|---|
| `verification_id` | UUID for verify / resend |
| `expires_in` | Seconds until this challenge expires |
| `resend_available_at` | ISO-8601 timestamp when OTP resend is allowed |
| `token` | Always empty string until verify succeeds |

#### Errors

**Validation failure — `422`**

```json
{
  "success": false,
  "data": [],
  "errors": {
    "email": ["The email has already been taken."],
    "phone": ["The phone has already been taken."]
  },
  "message": "Validation Failed",
  "token": ""
}
```

**OTP send cooldown (too soon after a previous send) — `422`**  
(Laravel validation shape, not the envelope above)

```json
{
  "message": "Please wait 42 seconds before requesting another code.",
  "errors": {
    "phone": [
      "Please wait 42 seconds before requesting another code."
    ]
  }
}
```

**Unexpected server failure — `400`**

```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "something went wrong",
  "token": ""
}
```

---

### 2. Login

`POST /api/v1/user/auth/login`  
**Auth:** none  
**Content-Type:** `application/json` (or form)

#### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `phone` | string | yes | No password |

#### Success — `200`

Same shape as register:

```json
{
  "success": true,
  "data": {
    "verification_id": "9f3c2a1b-0000-4000-8000-000000000002",
    "expires_in": 900,
    "resend_available_at": "2026-07-28T21:16:00+00:00"
  },
  "errors": {},
  "message": "",
  "token": ""
}
```

#### Errors

**Validation failure — `422`** (missing `phone`, etc.) — same envelope as register validation (`message`: `"Validation Failed"`).

**Domain failures — `400`**

```json
{
  "success": false,
  "data": [],
  "errors": {},
  "message": "user not found",
  "token": ""
}
```

Possible `message` values:

| Condition | `message` |
|---|---|
| Unknown phone | `user not found` |
| Deleted account | `this account is deleted` |
| Temporarily blocked | `this account is blocked` |
| Banned | `this account is banned` |
| Other inactive | `this account is not active ` |

**OTP send cooldown — `422`** — same Laravel validation shape as register cooldown.

---

### 3. Verify OTP (login / register challenge)

`POST /api/v1/otp/verify`  
**Auth:** none

#### Request body

| Field | Type | Required | Notes |
|---|---|---|---|
| `verification_id` | string (UUID) | yes | From login/register/resend |
| `code` | string | yes | SMS OTP code |
| `player_id` | string | no | Optional FCM registration token; upserted onto `device_tokens` on success (legacy field name kept for mobile) |

#### Success — `200`

```json
{
  "success": true,
  "data": {
    "access_token": "AbCdEfGhIjKlMnOpQrStUvWxYz0123456789",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "socket_id": "user-1",
      "name": "Jane Doe",
      "f_name": "Jane",
      "l_name": "Doe",
      "phone": "966512345678",
      "image": "https://…",
      "language": "en",
      "latitude": "24.7136",
      "longitude": "46.6753",
      "email": "jane@example.com",
      "nationality_id": 1,
      "nationality": {
        "id": 1,
        "name": "Saudi Arabia"
      }
    }
  },
  "errors": {},
  "message": "",
  "token": "AbCdEfGhIjKlMnOpQrStUvWxYz0123456789"
}
```

Notes:

- Use **`data.access_token`** (or envelope `token` — identical) for subsequent requests.
- The value is the **plain** token string (not `id|token`).
- Send as: `Authorization: Bearer {access_token}`.
- **Multi-device:** verifying OTP on a second device creates an additional Sanctum session; it does **not** revoke tokens on other devices.
- One token grants access to **all** authenticated mobile endpoints after verification. You do not need to handle token “abilities” or scopes.

#### Error: wrong code — `422`

```json
{
  "success": false,
  "data": {
    "code": "invalid_code",
    "attempts_remaining": 4
  },
  "errors": {},
  "message": "wrong OTP",
  "token": ""
}
```

#### Error: too many attempts — `422`

```json
{
  "success": false,
  "data": {
    "code": "max_attempts_exceeded"
  },
  "errors": {},
  "message": "max attempts exceeded",
  "token": ""
}
```

#### Error: expired / unknown `verification_id` — `422`

```json
{
  "success": false,
  "data": {
    "code": "verification_expired"
  },
  "errors": {},
  "message": "verification expired",
  "token": ""
}
```

(Unknown and expired IDs both use `verification_expired`.)

#### Validation failure — `422`

Missing/invalid fields → envelope with `"message": "Validation Failed"` and field errors under `errors`.

---

### 4. Resend OTP

`POST /api/v1/otp/resend`  
**Auth:** none

#### Request body

| Field | Type | Required |
|---|---|---|
| `verification_id` | string (UUID) | yes |

#### Success — `200`

Same challenge shape as login/register. **`verification_id` is unchanged** (same UUID). `expires_in` and `resend_available_at` are refreshed.

```json
{
  "success": true,
  "data": {
    "verification_id": "9f3c2a1b-0000-4000-8000-000000000002",
    "expires_in": 900,
    "resend_available_at": "2026-07-28T21:17:00+00:00"
  },
  "errors": {},
  "message": "",
  "token": ""
}
```

#### Errors

Same session error codes as verify when the challenge is expired / maxed out (`verification_expired`, `max_attempts_exceeded`) — **`422`**.

Cooldown cooldown before another SMS — **`422`** Laravel shape on `phone` (same as login/register cooldown).

---

## Authentication on protected endpoints

```http
Authorization: Bearer {access_token}
```

Examples of protected mobile routes (after verify):

- `GET /api/v1/user/auth/me`
- `POST /api/v1/user/auth/logout` — **this device only** (no body required; revokes the current Sanctum token and clears the FCM registration linked to that session)
- `POST /api/v1/user/auth/logout-all` — **every device** (all Sanctum tokens + all `device_tokens`)
- `POST /api/v1/user/auth/profile/update`
- `GET /api/v1/user/providers/get`
- `/api/v1/user/orders…`
- Plus shared authenticated APIs (wallet, chats, jobs, etc.) that accept a Bearer token

Missing/invalid token → typically **401**.

**Multi-device sessions:** logging in on phone B no longer kicks phone A offline. Use `logout-all` when the user explicitly wants every session gone (e.g. “sign out everywhere”).

---

## Error code reference

| `data.code` | HTTP | Meaning | Suggested UX |
|---|---|---|---|
| `invalid_code` | 422 | Wrong OTP; attempt counted | Show error + `attempts_remaining`; let user retry |
| `max_attempts_exceeded` | 422 | Too many wrong codes | Block further tries; restart login/register |
| `verification_expired` | 422 | Challenge expired or unknown ID | Restart login/register for a new `verification_id` |
| _(none — validation)_ | 422 | Request body invalid | Highlight fields from `errors` |
| _(none — cooldown)_ | 422 | SMS resend too soon | Disable Resend until `resend_available_at` / wait message |
| _(none — login domain)_ | 400 | User not found / blocked / etc. | Show `message` |

---

## Important limits & notes

| Setting | Default | Client impact |
|---|---|---|
| Challenge lifetime | **15 minutes** | Honor `expires_in`; on expiry restart flow |
| Max verify attempts | **5** | Use `attempts_remaining`; then `max_attempts_exceeded` |
| OTP resend cooldown | **60 seconds** | Honor `resend_available_at` before calling resend |
| Register upload | multipart + `image` | Do not send register as pure JSON |

**Headers worth sending**

- `Accept: application/json` (API forces JSON Accept anyway)
- `Accept-Language: en` / `ar` / … (affects human-readable messages)

**Do not** treat login/register envelope `token` as authenticated access — it is always `""` until verify succeeds.

**Token scoping:** the client does not need to manage abilities or scopes. After a successful verify, a single `access_token` is enough for authenticated mobile API calls.
