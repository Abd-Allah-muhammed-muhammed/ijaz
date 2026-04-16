# Copilot Instructions

## First — Always Do This

At the start of every session, read `PROJECT_CONTEXT.md` in the project root.
That file is the lean entry point for architecture, patterns, and conventions.
For detailed information, refer to these specialized documentation files:

- **`docs/API_INVENTORY.md`** — All endpoints with request/response details
- **`docs/MODELS_REFERENCE.md`** — All models with fields, relationships, traits
- **`docs/REFACTOR_NOTES.md`** — Known issues and refactor priorities
- **`docs/ENUMS_REFERENCE.md`** — All enums with cases, values, and usage patterns

Never rely on assumptions or general Laravel knowledge when the answer is in these files.

---

## Rules — Never Break These

**Controllers**
- Thin only — validate input, call a Service or Action, return response
- No business logic inside controllers

**Validation**
- Always use a dedicated `FormRequest` class
- Never validate inline inside the controller

**Enums**
- Always use the Enums from `app/Enums/` — never hardcode status strings
- Full reference in `docs/ENUMS_REFERENCE.md`
- Never use literal strings like `'pending'` or `'active'`; use `PaymentStatusEnum::Pending->value` or the enum directly

**API Responses**
- Always wrap data in a `Resource` class — never return raw models or arrays
- Always use `HasApiResponse` trait helpers:

```php
// Success with data
return $this->successResponse(new SomeResource($model));

// Success message only
return $this->successMessageResponse(__('messages.done'));

// Validation / logic error
return $this->failedMessageResponse(__('messages.error'), 422);
```

- Never mix raw `response()->json()` with `HasApiResponse` in the same controller

**Services & Actions**
- If logic touches more than one model → extract to `app/Services/`
- If logic is a single self-contained operation → extract to `app/Actions/`
- Full reference in `PROJECT_CONTEXT.md` Section 6

---

## Before Writing Any Code

1. Tell me which existing files are relevant
2. Check if a Service, Action, FormRequest, or Resource already exists for this
3. If anything contradicts `PROJECT_CONTEXT.md`, flag it — don't silently work around it

---

## Known Issues — Don't Repeat These

These are already documented in `docs/REFACTOR_NOTES.md`.
Don't generate new code that repeats them:

- ❌ No inline validation in controllers
- ❌ No raw arrays in API responses
- ❌ No hardcoded status strings
- ❌ No business logic inside controllers
- ❌ No new typos (see: `walletTTransactions`, `lastMassage`, `PropertiyCategory`)

---

## Keeping Context Fresh

After any session that adds a model, endpoint, enum, service, or changes a pattern —
remind me to update the relevant documentation files so they stay accurate:

- Update **`PROJECT_CONTEXT.md`** if: architecture or conventions change
- Update **`docs/API_INVENTORY.md`** if: endpoints are added/modified
- Update **`docs/MODELS_REFERENCE.md`** if: models are added/modified
- Update **`docs/ENUMS_REFERENCE.md`** if: enums are added or cases change
- Update **`docs/REFACTOR_NOTES.md`** if: issues are found or resolved
