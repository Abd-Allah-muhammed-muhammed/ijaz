import { cn } from '@/shared/lib/utils';
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
    <p {...props} className={cn('text-danger text-sm mb-0 mt-2', className)}>
      {message}
    </p>
  ) : null;
}
