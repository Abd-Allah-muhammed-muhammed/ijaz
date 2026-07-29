---
name: layered-architecture
description: "Enforce Ijaz's mandatory Controller → Service → Action → Repository / DTO / FormRequest layering whenever creating, editing, reviewing, or refactoring PHP in app/ or Modules/. Trigger on controllers, services, actions, repositories, DTOs, FormRequests, module extractions, and any architectural placement question. Also covers the explicit-imports rule and post-extraction verification (grep is not enough)."
---

# Layered Architecture (Ijaz)

Authoritative Cursor rule (keep in sync): `.cursor/rules/layered-architecture.mdc`.
Map / context: `PROJECT_CONTEXT.md` §5.

Every new feature, refactor, or fix in `app/` or `Modules/*` MUST follow:

```
Controller → Service → Action → Repository / DTO / Contracts
                              ↘ FormRequest (validation only, called by Controller)
```

## When to use this skill

Activate whenever you:

- Create or edit a Controller, Service, Action, Repository, DTO, or FormRequest under `app/` or `Modules/`
- Extract or move a domain into a module
- Review PHP for layer violations
- Decide where business logic, Eloquent queries, or validation belongs

## Rules (non-negotiable)

1. **Controllers contain ZERO business logic.** A Controller method may only:
   - Accept a FormRequest (validation happens there, not in the controller)
   - Call exactly one Service method
   - Map the Service's return value (a DTO or result object) into an HTTP response
   - Never: query the database directly, contain if/else business rules, call other Controllers, or manipulate Eloquent models directly

2. **Services orchestrate, they do not implement.** A Service method may only:
   - Call one or more Actions
   - Wrap Actions in a DB transaction when multiple writes must be atomic
   - Never: contain the actual business logic itself, query Eloquent directly, or format HTTP responses

3. **Actions do the real work.** Each Action is a single-purpose class with one `handle()` method that performs ONE unit of business logic. Actions may:
   - Call a Repository to read/write data
   - Call other Actions (composition) when a step is itself reusable
   - Return a DTO representing the result
   - Never: know about HTTP (no Request/Response objects), never contain validation logic

4. **Repositories are the ONLY layer allowed to talk to Eloquent directly.** Queries go through a Repository bound via a `*RepositoryInterface` contract. Do not skip this layer "because it's simple."

5. **DTOs carry data between layers.** Prefer `final readonly class` DTOs with public promoted properties (match existing project style). Do not leak raw arrays or Eloquent models across layers unless an established local pattern requires it.

6. **FormRequests own validation ONLY.** Authorization / ownership checks that need other data belong in the Controller or Service as an explicit guard — follow Auth-domain precedent for `authorize()` placement.

## Where this applies

- Every module under `Modules/*` (Payment, Wallet, Sms, Chat, Guarantor, Opportunity, Catalog, Classifieds, Geo, Orders, Marketplace, Cms, Support, Jobs, …)
- Remaining domains under `app/` (core Auth, Admin/User/Provider management, Dashboard aggregator, etc.)

## Reference implementations

| Pattern | Location |
|---|---|
| Simplest full chain | `app/Services/Auth/AdminAuthService.php` + `app/Actions/Auth/Admin/LoginAdminAction.php` + `app/Repositories/Auth/AdminRepository.php` |
| Multi-action service | `app/Services/Auth/UserAuthService.php` + `app/Actions/Auth/User/*` |
| Driver / strategy service | `Modules/Payment/Services/PaymentService.php` + `Modules/Payment/Gateways/*` |
| Same driver pattern | `Modules/Sms/Services/SmsService.php` + `Modules/Sms/Gateways/*` |
| Domain modules | Geo, Orders, and siblings under `Modules/*` |

## Explicit imports — no same-namespace fallback

Always use an explicit `use` statement for every class reference, even if the class currently lives in the same namespace.

Why: after `NationalityResource` moved from `App\Http\Resources\Dashboard\` to `Modules\Geo\…`, same-namespace short-name usage in `UserResource` broke at runtime ("Class not found"), and grep for the old FQCN found nothing because there was never an explicit import to search for.

**Pint conflict:** the `laravel` preset's `no_unused_imports` strips same-namespace `use` statements. Do **not** disable that rule project-wide. When an intentional same-namespace import is required, use a **distinct alias** so Pint keeps it:

```php
namespace App\Support;

// Distinct alias required — Pint would strip a same-name alias.
use App\Support\Normalize as TextNormalize;

TextNormalize::make($value, $locale);
```

Reference: `app/Support/HasNormalizedAttributes.php`.

## Post-extraction verification — grep is not enough

After moving any class to a new module namespace:

1. Grep for the OLD fully-qualified class name (necessary)
2. Exercise every Dashboard/API route that could touch the moved class (feature test or smoke-check)

Grep alone misses unqualified same-namespace references and dynamic class resolution (`$class::make()`, string-based resolution). A clean grep is not proof the extraction is safe.

## Review flags

When reviewing or writing code, if you find:

- A Controller with an `if` checking business state (not just null-checking a Service result) → move to Service/Action
- A Service calling `Model::where(...)` or `$model->save()` → extract to a Repository
- An Action that accepts a `Request` object → receive plain arguments/DTOs instead
- Logic duplicated across two Controllers → extract one shared Action

Always propose the correct layer placement before writing code — this is the project's established convention, not optional per-feature.
