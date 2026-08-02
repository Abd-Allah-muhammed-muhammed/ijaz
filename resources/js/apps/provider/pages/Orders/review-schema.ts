import {z} from 'zod';
import i18next from '@/lang/i18next';

const required = (attributeKey: string) => () =>
  i18next.t('validation.required', {attribute: i18next.t(attributeKey)});

export const ReviewSchema = z.object({
  rating: z.number({error: required('rating')}),

  comment: z.string({error: () => i18next.t('validation.string', {attribute: i18next.t('description')})})
    .trim()
    .nonempty({error: required('description')})
    .min(3, {error: () => i18next.t('validation.min.numeric', {attribute: i18next.t('description'), min: '3'})})
    .max(1000, {error: () => i18next.t('validation.max.numeric', {attribute: i18next.t('description'), max: '1000'})}),
});


export type ReviewSchemaType = z.infer<typeof ReviewSchema>;
