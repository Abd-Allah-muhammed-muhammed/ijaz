import { z } from 'zod';
import i18next from '@/lang/i18next';

const required = (attributeKey: string) => () =>
  i18next.t('validation.required', { attribute: i18next.t(attributeKey) });

export const OfferSchema = z.object({
  price: z.number({ error: required('price') })
    .positive({ error: () => i18next.t('validation.gt.numeric', { attribute: i18next.t('price'), value: '0' }) }),

  description: z.string({ error: () => i18next.t('validation.string', { attribute: i18next.t('description') }) })
    .trim()
    .nonempty({ error: required('description') })
    .min(3, { error: () => i18next.t('validation.min.numeric', { attribute: i18next.t('description'), min: '3' }) })
    .max(1000, { error: () => i18next.t('validation.max.numeric', { attribute: i18next.t('description'), max: '1000' }) }),
});


export type OfferSchemaType = z.infer<typeof OfferSchema>;
