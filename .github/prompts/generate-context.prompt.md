---
agent: agent
description: Full scan of the entire codebase. Generates or fully refreshes all context files. Run this at project start or after a major milestone.
---

## WHEN TO USE THIS

Run this prompt when:
- Starting fresh on this project
- After a major milestone or sprint end
- When context files are missing or outdated
- When the architecture changes significantly

For smaller updates after a single feature, use `.github/prompts/update-context.prompt.md` instead.

---

## WHAT THIS PRODUCES

```
PROJECT_CONTEXT.md              ← lean master (architecture + patterns + pointers)
docs/API_INVENTORY.md           ← all endpoints with full request/response details
docs/MODELS_REFERENCE.md        ← all models, fields, relationships, traits
docs/REFACTOR_NOTES.md          ← all issues and refactor risks
docs/ENUMS_REFERENCE.md         ← all enums with cases and values
```

If any of these files already exist, **overwrite them completely** — do not merge.
A full refresh means starting clean from the actual code.

---

## STEP 1 — Full Codebase Scan

Work through this checklist in order. Read every file — do not skip.

### Package & Config
- [ ] `composer.json` — exact package versions
- [ ] `package.json` — exact frontend dependencies
- [ ] `config/auth.php` — every guard and provider
- [ ] `config/app.php` — timezone, locale, name
- [ ] `bootstrap/app.php` — registered middleware, exception handling
- [ ] `.env.example` — available environment variables

### Routes — read every file fully
- [ ] `routes/api.php`
- [ ] Every file inside `routes/Api/` recursively
- [ ] `routes/web.php`
- [ ] `routes/dashboard.php`
- [ ] `routes/provider.php`
- [ ] `routes/channels.php`

### Models — read every file in `app/Models/` fully
For each model extract: table name, fillable fields, casts, relationships, traits used, scopes, enums used.

### Controllers — read every file in these directories fully
- [ ] `app/Http/Controllers/Api/V1/`
- [ ] `app/Http/Controllers/Auth/`
- [ ] `app/Http/Controllers/Dashboard/`
- [ ] `app/Http/Controllers/Frontend/`
- [ ] `app/Http/Controllers/Provider/`
- [ ] `app/Http/Controllers/Payments/`

### Business Logic
- [ ] Every file in `app/Services/` recursively
- [ ] Every file in `app/Actions/` recursively

### Enums — read every file in `app/Enums/` fully
Extract every case with its value.

### Traits — read every file in `app/Traits/` fully

### HTTP Layer
- [ ] Every file in `app/Http/Requests/`
- [ ] Every file in `app/Http/Resources/`
- [ ] Every file in `app/Http/Middleware/`

### Async & Events
- [ ] Every file in `app/Jobs/`
- [ ] Every file in `app/Events/`
- [ ] Every file in `app/Notifications/`
- [ ] Every file in `app/Observers/`

### Database
- [ ] Every file in `database/migrations/`
- [ ] Every file in `database/seeders/`

### Libraries
- [ ] Every file in `lib/` recursively

---

## STEP 2 — Generate `docs/API_INVENTORY.md`

Group endpoints by domain. For each endpoint:

| Method | Path | Controller@Method | Auth | Request Class | Resource Class | Description |
|--------|------|-------------------|------|---------------|----------------|-------------|

For each domain section also document:
- Request body fields (from FormRequest rules)
- Response structure (from Resource class or controller return)
- Any inconsistencies found (flag with ⚠️)

---

## STEP 3 — Generate `docs/MODELS_REFERENCE.md`

For every model in `app/Models/`:

**Model: `[Name]`**
- File: `app/Models/[Name].php`
- Table: `[table_name]`
- Traits: [list]
- Relationships: [list with type and target]
- Enums used: [field → EnumClass]
- Scopes: [list]
- Notable: [anything unusual or worth flagging]

---

## STEP 4 — Generate `docs/ENUMS_REFERENCE.md`

For every enum in `app/Enums/`:

**`[Namespace\EnumName]`**
File: `app/Enums/[path]`

| Case | Value |
|------|-------|
| ... | ... |

Include enum utility traits at the bottom.

---

## STEP 5 — Generate `docs/REFACTOR_NOTES.md`

For every issue found during the scan:

| File | Issue | Severity | Suggestion |
|------|-------|----------|------------|

Severity: High / Medium / Low

End with a "Biggest Risks" section listing the top 3 things that could break.

Flag patterns to watch for:
- Fat controllers (business logic inside controller methods)
- Inline validation instead of FormRequest
- Raw arrays returned instead of Resource classes
- Hardcoded status strings instead of Enums
- N+1 query risks (missing eager loading)
- Typos in method/class/column names
- Inconsistent response shapes across endpoints
- Non-RESTful verb usage for state changes

---

## STEP 6 — Generate `PROJECT_CONTEXT.md` (lean master)

Write this last — after all docs/ files are complete.

### Section 1 — How To Use This Documentation
```
This file is the entry point. For full details, refer to:
- docs/API_INVENTORY.md     — all endpoints with request/response details
- docs/MODELS_REFERENCE.md  — all models, fields, relationships, traits
- docs/REFACTOR_NOTES.md    — known issues and refactor plan
- docs/ENUMS_REFERENCE.md   — all enums with their cases and values

Last verified from source: [today's date]
```

### Section 2 — Project Overview
What the app does, the three actors, exact tech stack versions.

### Section 3 — Authentication & Guards
Full guards table with models, routes, and token behavior.
Keep this here — every feature needs it.

### Section 4 — Directory Structure
Annotated tree of what lives where.
Flag non-standard directories.

### Section 5 — Architecture Patterns
- Thin controller rule (validate → service/action → return)
- When to use Service vs Action
- Payment flow
- Real-time flow
- File upload pattern
- Translation pattern

### Section 6 — Services & Actions Overview
For each Service/Action: purpose, key methods, what models it touches.

### Section 7 — Conventions & Rules
Naming conventions with real examples from the code.
The "never do this" list based on what was actually found.

### Section 8 — Top Issues Summary
```
Full list is in docs/REFACTOR_NOTES.md

Top 3 priorities:
1. [highest severity issue]
2. [second]
3. [third]
```

---

## OUTPUT RULES

1. Write only what you found in the actual code — never guess
2. If a file is a stub or empty, note it explicitly
3. If unsure about something, prefix with `⚠️ Unverified:`
4. Write files in this order: API_INVENTORY → MODELS_REFERENCE → ENUMS_REFERENCE → REFACTOR_NOTES → PROJECT_CONTEXT
5. PROJECT_CONTEXT.md is always written last
6. After finishing, output a summary:
   - Date of generation
   - Count: endpoints / models / enums / issues found
   - Top 5 most important issues
