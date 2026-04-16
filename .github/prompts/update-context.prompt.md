---
agent: agent
description: Partial update of context files after adding or modifying a feature. Faster than a full scan — only updates what changed.
---

## WHEN TO USE THIS

Run this prompt after:
- Adding a new API endpoint
- Adding or modifying a model
- Adding a new Service or Action
- Adding a new Enum or modifying existing cases
- Fixing a known issue from REFACTOR_NOTES.md

Do NOT use this after major structural changes — run `generate-context.prompt.md` instead.

---

## BEFORE YOU START

Tell me what you just added or changed.
If you don't specify, I will ask before proceeding.

Example inputs:
- "I just added the PromoCode feature with a model, migration, controller, and endpoints"
- "I refactored OrderController and extracted logic to OrderService"
- "I added a new enum case to OrderStatusEnum"
- "I fixed the walletTTransactions typo"

---

## STEP 1 — Identify affected files

Based on what changed, identify which files need to be read:

**If a new model was added:**
- Read the new model file in `app/Models/`
- Read the new migration in `database/migrations/`
- Read any new Enum files in `app/Enums/`
- Read any new Trait files in `app/Traits/`

**If a new endpoint was added:**
- Read the controller method(s) added/changed
- Read the FormRequest class(es)
- Read the Resource class(es)
- Read the route file where it was added

**If a Service or Action was added/changed:**
- Read the Service/Action file fully
- Read any models it touches

**If an Enum was added/changed:**
- Read the Enum file fully

**If a bug or typo was fixed:**
- Read the fixed file
- Note which issue from `docs/REFACTOR_NOTES.md` this resolves

Read all identified files before making any updates.

---

## STEP 2 — Update `docs/API_INVENTORY.md`

Only if endpoints were added or changed.

- Add new endpoints in the correct domain section
- Update changed endpoints (method, path, auth, request, resource, description)
- Remove endpoints that were deleted
- Flag any new inconsistencies found with ⚠️

Do not touch sections unrelated to this change.

---

## STEP 3 — Update `docs/MODELS_REFERENCE.md`

Only if models, relationships, traits, or migrations were added or changed.

- Add new model entries in full format
- Update changed model entries (fields, relationships, traits, enums)
- Remove entries for deleted models

Do not touch entries unrelated to this change.

---

## STEP 4 — Update `docs/ENUMS_REFERENCE.md`

Only if enums were added or modified.

- Add new enum entries
- Update changed cases or values
- Remove deleted enums

---

## STEP 5 — Update `docs/REFACTOR_NOTES.md`

Always check this file, even for small changes.

**If a known issue was fixed:**
- Remove or mark the issue as resolved:
  `✅ Resolved: [brief description of fix] — [today's date]`

**If the change introduced a new issue:**
- Add a new row to the issues table

**If the change was a partial fix:**
- Update the existing row to reflect current state

---

## STEP 6 — Update `PROJECT_CONTEXT.md`

Only update the sections that are actually affected.

**Always update:**
- Section 1: bump "Last verified from source" date

**Update only if relevant:**
- Section 2: if a new actor or tech dependency was added
- Section 3: if directory structure changed
- Section 4: if a new directory pattern was introduced
- Section 5: if architecture patterns changed
- Section 6: if a new Service or Action was added
- Section 7: if a naming convention changed or a new rule applies
- Section 8: refresh top issues list from `docs/REFACTOR_NOTES.md`

Do not rewrite sections that were not affected by this change.

---

## STEP 7 — Summary

After finishing, output:

```
Updated files:
- [list of files changed]

Changes made:
- [brief description per file]

Issues resolved: [number or "none"]
New issues found: [number or "none"]

Context is current as of: [today's date]
```

---

## OUTPUT RULES

1. Only read and update files relevant to the change — do not do a full scan
2. Never remove information that is still accurate
3. If you find something unrelated that looks wrong, add it to REFACTOR_NOTES.md but do not fix it
4. If the change contradicts something in the existing context, update the context and note the contradiction
5. Always update the "Last verified" date in PROJECT_CONTEXT.md
