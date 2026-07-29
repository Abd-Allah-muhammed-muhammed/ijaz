import type { ZodError, ZodIssue } from 'zod';

/**
 * Error-display contract (Inertia + InputError + TranslatableInputs):
 *
 * - Keys use Laravel/Inertia dotted paths: `email`, `translations.ar.title`
 * - Values are a single string message per field (first Zod issue wins)
 * - Consume via `<InputError message={form.errors['translations.ar.title']} />`
 *   or `errors[\`translations.${locale}.${field}\`]` in TranslatableInputs
 */
export type FormFieldErrors = Record<string, string>;

/**
 * Maps Zod issues to the dotted-key error map used by Inertia's `form.errors`
 * and our InputError / TranslatableInputs components.
 */
export function zodIssuesToFormErrors(issues: readonly ZodIssue[]): FormFieldErrors {
  const errors: FormFieldErrors = {};

  for (const issue of issues) {
    const key = issue.path.map(String).join('.');

    if (key === '' || errors[key] !== undefined) {
      continue;
    }

    errors[key] = issue.message;
  }

  return errors;
}

export function zodErrorToFormErrors(error: ZodError): FormFieldErrors {
  return zodIssuesToFormErrors(error.issues);
}
