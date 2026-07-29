import { z, ZodObject } from 'zod';

import { getSupportedLocales } from '@/hooks/use-locales';

const locales = getSupportedLocales();

export const Inputs = z.object({
  translations: z.object(
    Object.keys(locales).reduce(
      (acc, locale) => {
        acc[locale] = z.object({
          title: z
            .string()
            .min(1, { message: 'Title is required' })
            .max(255, { message: 'Title must be less than 255 characters' }),
          answer: z.string().min(1, { message: 'Answer is required' }),
        });
        return acc;
      },
      {} as Record<string, ZodObject>,
    ),
  ),
});

export type Inputs = z.infer<typeof Inputs>;
