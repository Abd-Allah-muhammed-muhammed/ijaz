# useAppForm — form + Zod foundation

Canonical client-side form pattern for future Create/Edit migrations.
Do **not** invent a second validation/error shape alongside this.

## Contract

| Piece | Source |
| --- | --- |
| Form state / submit | Inertia `useForm` (via `useAppForm`) |
| Client validation | Zod schema + `zodValidate` / `form.validate()` |
| Field errors | Dotted keys on `form.errors` (same as Laravel) |
| Display | `@/shared/components/inputs/InputError` and `TranslatableInputs` |

Nested example key: `translations.ar.title` →
`<InputError message={form.errors['translations.ar.title']} />`

## Minimal create page

```tsx
import InputError from '@/shared/components/inputs/InputError';
import { useAppForm } from '@/shared/hooks/use-app-form';
import ExampleController from '@/actions/.../ExampleController';
import { z } from 'zod';

const schema = z.object({
  name: z.string().min(1, { message: 'Name is required' }),
});

export default function Create() {
  const form = useAppForm(schema, { name: '' });

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(ExampleController.store());
      }}
    >
      <input
        value={form.data.name}
        onChange={(e) => form.setData('name', e.target.value)}
      />
      <InputError message={form.errors.name} />
      <button type="submit" disabled={form.processing}>
        Save
      </button>
    </form>
  );
}
```

## Notes

- `form.post` / `put` / `patch` / `delete` / `submit` / `get` all validate first.
- Call `form.validate(extra)` when you need runtime-only context (legacy `zodValidate` `extra` arg).
- Existing pages may keep `useForm` + `zodValidate` until migrated; new forms should use `useAppForm`.
- Shared mapping lives in `@/shared/lib/zod-form-errors` — do not duplicate issue→errors logic.
