import { zodValidate } from '@/shared/helpers/general';
import type {
  FormDataConvertible,
  UseFormSubmitArguments,
  UseFormSubmitOptions,
} from '@inertiajs/core';
import { type InertiaFormProps, useForm } from '@inertiajs/react';
import type { z } from 'zod';

/**
 * Shared app form helper — Inertia `useForm` + Zod client validation.
 *
 * ## Intended usage (future Create/Edit migrations)
 *
 * ```tsx
 * import { useAppForm } from '@/shared/hooks/use-app-form';
 * import InputError from '@/shared/components/inputs/InputError';
 * import TranslatableInputs from '@/shared/components/inputs/TranslatableInputs';
 * import { z } from 'zod';
 * import ExampleController from '@/actions/.../ExampleController';
 *
 * const schema = z.object({
 *   name: z.string().min(1, { message: 'Name is required' }),
 *   translations: z.object({
 *     ar: z.object({ title: z.string().min(1) }),
 *     en: z.object({ title: z.string().min(1) }),
 *   }),
 * });
 *
 * type FormValues = z.infer<typeof schema>;
 *
 * export default function Create() {
 *   const form = useAppForm(schema, {
 *     name: '',
 *     translations: { ar: { title: '' }, en: { title: '' } },
 *   });
 *
 *   return (
 *     <form
 *       onSubmit={(e) => {
 *         e.preventDefault();
 *         // validate() runs automatically; only posts when Zod succeeds
 *         form.post(ExampleController.store().url);
 *         // or Wayfinder pair:
 *         // form.submit(ExampleController.store());
 *       }}
 *     >
 *       <input
 *         value={form.data.name}
 *         onChange={(e) => form.setData('name', e.target.value)}
 *       />
 *       <InputError message={form.errors.name} />
 *
 *       <TranslatableInputs
 *         field="title"
 *         values={form.data.translations}
 *         errors={form.errors}
 *         onChange={(locale, value) =>
 *           form.setData('translations', {
 *             ...form.data.translations,
 *             [locale]: { ...form.data.translations[locale], title: value },
 *           })
 *         }
 *       />
 *
 *       <button type="submit" disabled={form.processing}>Save</button>
 *     </form>
 *   );
 * }
 * ```
 *
 * ## Error contract
 *
 * Zod paths are joined with `.` and written via Inertia `setError`, matching
 * Laravel validation keys. Display with `InputError` / `TranslatableInputs` only —
 * do not invent a parallel errors shape.
 *
 * ## Extra / conditional fields
 *
 * Pass runtime-only context (e.g. edit id, requiredFiles) into `validate(extra)`
 * the same way existing `zodValidate(schema, form, extra)` callers do. Prefer
 * putting those fields in the schema + initial values when possible.
 *
 * @see resources/js/hooks/use-app-form.md
 */
export type AppFormData = Record<string, FormDataConvertible>;

export type UseAppFormReturn<TForm extends AppFormData> = InertiaFormProps<TForm> & {
  /**
   * Run Zod against current form data (+ optional extra), clear prior errors,
   * and set Inertia field errors on failure. Returns whether validation passed.
   */
  validate: (extra?: Record<string, unknown>) => boolean;
};

export function useAppForm<TSchema extends z.ZodType<AppFormData, AppFormData>>(
  schema: TSchema,
  initialValues: z.infer<TSchema>,
): UseAppFormReturn<z.infer<TSchema>> {
  type TForm = z.infer<TSchema>;

  const form = useForm<TForm>(initialValues);

  const validate = (extra: Record<string, unknown> = {}): boolean => {
    return zodValidate(schema, form, extra);
  };

  const submit = (...args: UseFormSubmitArguments): void => {
    if (!validate()) {
      return;
    }

    form.submit(...args);
  };

  const post = (url: string, options?: UseFormSubmitOptions): void => {
    if (!validate()) {
      return;
    }

    form.post(url, options);
  };

  const put = (url: string, options?: UseFormSubmitOptions): void => {
    if (!validate()) {
      return;
    }

    form.put(url, options);
  };

  const patch = (url: string, options?: UseFormSubmitOptions): void => {
    if (!validate()) {
      return;
    }

    form.patch(url, options);
  };

  const destroy = (url: string, options?: UseFormSubmitOptions): void => {
    if (!validate()) {
      return;
    }

    form.delete(url, options);
  };

  const get = (url: string, options?: UseFormSubmitOptions): void => {
    if (!validate()) {
      return;
    }

    form.get(url, options);
  };

  return {
    ...form,
    validate,
    submit,
    get,
    post,
    put,
    patch,
    delete: destroy,
  };
}
