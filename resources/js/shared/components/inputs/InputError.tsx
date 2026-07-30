import { type HTMLAttributes } from 'react';

/**
 * Canonical field-error display. Expects Inertia/Laravel dotted keys via
 * `message={form.errors.field}` or `message={form.errors['translations.ar.title']}`.
 *
 * @see useAppForm / @/shared/lib/zod-form-errors for the error-map contract
 */
export default function InputError({
  message,
  className = '',
  ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
  return message ? (
    <p {...props} className={`text-danger small mb-0 mt-2 ${className}`.trim()}>
      {message}
    </p>
  ) : null;
}
