# Contributing

## Core Principles

- Keep controllers thin.
- Use dedicated FormRequest classes for validation.
- Keep business logic in Services or Actions.
- Use enums from `app/Enums` instead of hardcoded status strings.
- Return API data via Resources and `HasApiResponse` helpers.

## Branch and Pull Request Workflow

1. Create a feature branch from `main`.
2. Keep changes focused and scoped to one feature/fix.
3. Add or update tests for behavior changes.
4. Run formatting and tests before opening a PR.
5. Include a clear PR description with risk and rollback notes for sensitive changes.

## Development Checks

- PHP format:
  - `vendor/bin/pint --dirty --format agent`
- Tests:
  - `php artisan test --compact`
- Optional route sanity check:
  - `php artisan route:list --except-vendor`

## Documentation Update Rules

When your change affects architecture or behavior, update docs in the same PR:

- `PROJECT_CONTEXT.md` for architecture and conventions
- `docs/API_INVENTORY.md` for endpoint or payload changes
- `docs/MODELS_REFERENCE.md` for model changes
- `docs/ENUMS_REFERENCE.md` for enum/case changes
- `docs/REFACTOR_NOTES.md` for discovered or resolved issues

## Naming and Compatibility

- Follow existing naming conventions and file organization.
- Avoid introducing new typos and naming drift.
- For breaking changes, provide a migration path and versioning plan.
