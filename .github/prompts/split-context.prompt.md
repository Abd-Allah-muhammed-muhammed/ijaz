---
agent: agent
description: Split PROJECT_CONTEXT.md into a lean master file and detailed docs, with zero information loss.
---

Your job is to restructure the project documentation without losing a single piece of information.

## CURRENT STATE

Right now there is one large file: `PROJECT_CONTEXT.md` in the project root.
There are also existing files in `docs/`:
- `docs/API_INVENTORY.md`
- `docs/MODELS_REFERENCE.md`
- `docs/REFACTOR_NOTES.md`

## TARGET STATE

```
PROJECT_CONTEXT.md          ← lean master file (architecture + patterns + pointers)
docs/
├── API_INVENTORY.md        ← all endpoint details (merged + deduplicated)
├── MODELS_REFERENCE.md     ← all model details (merged + deduplicated)
├── REFACTOR_NOTES.md       ← all issues and refactor plan (merged + deduplicated)
└── ENUMS_REFERENCE.md      ← new: all enums extracted from PROJECT_CONTEXT.md
```

---

## STEP 1 — Read everything first

Before writing a single file, read all of these fully:
- `PROJECT_CONTEXT.md`
- `docs/API_INVENTORY.md`
- `docs/MODELS_REFERENCE.md`
- `docs/REFACTOR_NOTES.md`

For each docs file, compare it against the matching section in `PROJECT_CONTEXT.md` and note:
- What exists only in `PROJECT_CONTEXT.md`
- What exists only in the docs file
- What exists in both (duplicates to merge)

Do not start writing until you have completed this comparison for all four files.

---

## STEP 2 — Update `docs/API_INVENTORY.md`

Merge into this file everything API-related from `PROJECT_CONTEXT.md` that is not already there.

This includes:
- Section 4 (All API Endpoints V1) from `PROJECT_CONTEXT.md`
- The actual request/response shapes and auth details
- Any endpoint notes or inconsistencies mentioned in `PROJECT_CONTEXT.md`

Rules:
- Keep the existing structure of `docs/API_INVENTORY.md`
- Add any missing endpoints or details
- Remove exact duplicates
- If the same endpoint has different details in each file, keep the more detailed version and add a note: `⚠️ Conflicting info found — keeping most detailed version`
- Do not remove anything — only add and deduplicate

---

## STEP 3 — Update `docs/MODELS_REFERENCE.md`

Merge into this file everything model-related from `PROJECT_CONTEXT.md` that is not already there.

This includes:
- Section 5 (Core Models & Relationships)
- Section 7 (Traits Reference)
- Section 9 (Web & Dashboard Routes) if it references models

Rules:
- Same merge rules as Step 2
- For each model, the final entry must have: table name, key fields, relationships, traits, enums used, scopes
- If a model exists in both files with different details, merge them into one complete entry

---

## STEP 4 — Update `docs/REFACTOR_NOTES.md`

Merge into this file everything issue-related from `PROJECT_CONTEXT.md` that is not already there.

This includes:
- Section 15 (Inconsistencies & Issues)
- Section 14 patterns flagged with ⚠️
- Any issue mentioned anywhere in `PROJECT_CONTEXT.md`

Rules:
- Same merge rules as Step 2
- Keep the existing table format in `docs/REFACTOR_NOTES.md`
- If the same issue appears in both files, keep the more detailed description
- Add a "Biggest Risks" section at the bottom if not already present

---

## STEP 5 — Create `docs/ENUMS_REFERENCE.md`

Extract everything enum-related from `PROJECT_CONTEXT.md` into a new file.

Format:

```markdown
# Enums Reference

Generated from app/Enums/ — last verified [date from PROJECT_CONTEXT.md].

## [EnumName]
File: app/Enums/[path]

| Case | Value | Description |
|------|-------|-------------|
| ... | ... | ... |
```

Include every enum that was in `PROJECT_CONTEXT.md` Section 6.
Also include the enum utility traits (Collectable, HasOperations, etc.) at the bottom.

---

## STEP 6 — Rewrite `PROJECT_CONTEXT.md` (lean version)

Now rewrite `PROJECT_CONTEXT.md` to be the lean master file.

It must contain these sections and nothing else:

### Section 1 — How To Use This Documentation
```markdown
This file is the entry point. For full details, refer to:
- `docs/API_INVENTORY.md`     — all endpoints with request/response details
- `docs/MODELS_REFERENCE.md`  — all models, fields, relationships, traits
- `docs/REFACTOR_NOTES.md`    — known issues and refactor plan
- `docs/ENUMS_REFERENCE.md`   — all enums with their cases and values

Last verified from source: [keep the date from the original file]
```

### Section 2 — Project Overview
Keep exactly what was in the original. Do not summarize further.

### Section 3 — Authentication & Guards
Keep the full guards table and all auth details from the original.
This stays here because every feature needs it.

### Section 4 — Directory Structure
Keep the annotated directory tree.
Remove any content that is now in the docs files.

### Section 5 — Architecture Patterns
Keep:
- How controllers should be structured (thin controller rule)
- How Services vs Actions are used
- How payments flow
- How real-time works
- How file uploads work
- How translations work

Remove any raw data (endpoint tables, model field lists) — those are now in docs/.

### Section 6 — Services & Actions
Keep the services and actions overview from the original.
Include method names and what each service touches.
This stays here because it is architectural, not just data.

### Section 7 — Conventions & Rules
Keep all naming conventions, code style rules, and the "never do this" list.

### Section 8 — Known Issues Summary
A short pointer only:
```markdown
Full issue list is in `docs/REFACTOR_NOTES.md`.

Top priorities:
1. [copy the top 3 issues from REFACTOR_NOTES.md as a summary]
```

---

## STEP 7 — Verify nothing was lost

After writing all files, do a final check:

For each section in the original `PROJECT_CONTEXT.md`:
- Confirm which file it now lives in
- Confirm no content was dropped

Output a verification table like this:

| Original Section | Now In | Content Lost? |
|-----------------|--------|---------------|
| Section 1 - Overview | PROJECT_CONTEXT.md | No |
| Section 4 - API Endpoints | docs/API_INVENTORY.md | No |
| ... | ... | ... |

If anything was lost, fix it before finishing.

---

## OUTPUT RULES

1. Write files one at a time, in the order: API_INVENTORY → MODELS_REFERENCE → REFACTOR_NOTES → ENUMS_REFERENCE → PROJECT_CONTEXT
2. Write PROJECT_CONTEXT.md last — only after the other four are complete
3. Never delete information — only move and deduplicate
4. If you find a conflict between the same information in two files, keep the more detailed version and add ⚠️
5. When finished, tell me: how many endpoints are now in API_INVENTORY, how many models in MODELS_REFERENCE, how many issues in REFACTOR_NOTES, how many enums in ENUMS_REFERENCE
