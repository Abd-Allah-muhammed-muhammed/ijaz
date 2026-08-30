import { z, ZodObject } from 'zod';

import { getSupportedLocales } from '@/shared/hooks/use-locales';

const locales = getSupportedLocales();

export const Inputs = z
  .object({
    slug: z.string().min(1).max(255),
    composed_of_slugs: z.array(z.string()).optional().default([]),
    translations: z.object(
      Object.keys(locales).reduce(
        (acc, locale) => {
          acc[locale] = z.object({
            title: z
              .string()
              .min(2, { message: 'Title is required' })
              .max(191, { message: 'Title must be less than 191 characters' }),
            content: z.string().max(65535, { message: 'content must be less than 65535 characters' }),
          });
          return acc;
        },
        {} as Record<string, ZodObject>,
      ),
    ),
  })
  .superRefine((data, ctx) => {
    const isComposed = (data.composed_of_slugs?.length ?? 0) > 0;
    if (isComposed) {
      return;
    }

    for (const locale of Object.keys(locales)) {
      const content = data.translations?.[locale]?.content ?? '';
      if (content.trim().length < 2) {
        ctx.addIssue({
          code: 'custom',
          message: 'content is required',
          path: ['translations', locale, 'content'],
        });
      }
    }
  });

export type Inputs = z.infer<typeof Inputs>;
