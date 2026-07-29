
import { z } from 'zod';
import i18next from '@/lang/i18next';



export const OfferSchema = z.object({
  price: z.number(i18next.t('validation.required', { attribute: i18next.t('price') }))
    .positive(i18next.t('validation.gt.numeric', { attribute: i18next.t('price'), value: '0' })),

  description: z.string(i18next.t('validation.string', { attribute: i18next.t('description') }))
    .trim()
    .nonempty(i18next.t('validation.required', { attribute: i18next.t('description') }))
    .min(3, i18next.t('validation.min.numeric', { attribute: i18next.t('description'), min: '3' }))
    .max(1000, i18next.t('validation.max.numeric', { attribute: i18next.t('description'), max: '1000' })),
});


export type OfferSchemaType = z.infer<typeof OfferSchema>;
