import {z} from 'zod';
import i18next from '@/lang/i18next';


export const ReviewSchema = z.object({
  rating: z.number(i18next.t('validation.required', {attribute: i18next.t('rating')})),

  comment: z.string(i18next.t('validation.string', {attribute: i18next.t('description')}))
    .trim()
    .nonempty(i18next.t('validation.required', {attribute: i18next.t('description')}))
    .min(3, i18next.t('validation.min.numeric', {attribute: i18next.t('description'), min: '3'}))
    .max(1000, i18next.t('validation.max.numeric', {attribute: i18next.t('description'), max: '1000'})),
});


export type ReviewSchemaType = z.infer<typeof ReviewSchema>;
