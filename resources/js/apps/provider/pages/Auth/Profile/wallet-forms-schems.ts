import {z} from "zod";

import i18next from "@/lang/i18next";
import {PaymentMethodEnum} from "@/Enums/Payment";

const fileSize = 2; // 5 MB

const required = (attributeKey: string) => () =>
  i18next.t('validation.required', {attribute: i18next.t(attributeKey)});

export const walletDepositFormSchema = z.object({
  amount: z.number({error: required('amount')})
    .min(1, {error: () => i18next.t('validation.min.numeric', {
      'attribute': i18next.t('amount'),
      'min': String(1)
    })}),
  payment_method: z.string({error: required('payment_method')})
    .nonempty({error: required('payment_method')}),
  payment_driver: z.string().optional(),
  user_notes: z.string()
    .max(2000, {error: () => i18next.t('validation.max.string', {
      'attribute': i18next.t('user_note'),
      'max': String(2000)
    })})
    .optional(),
  transaction_image: z.file({error: required('transaction_image')})
    .max(fileSize * 1024 * 102, {error: () => i18next.t('validation.max.file', {
      'attribute': i18next.t('transaction_image'),
      'max': fileSize + 'MB'
    })})
    .mime(['image/jpeg', 'image/png'], {error: () => i18next.t('validation.mimes', {
      'attribute': i18next.t('transaction_image'),
      'values': 'png,jpeg'
    })})
    .optional(),
})
  .refine((data) => {
    if (data.payment_method === PaymentMethodEnum.Offline) {
      return data.transaction_image !== undefined;
    }
    return true;
  }, {
    error: required('transaction_image'),
    path: ['transaction_image']
  })
  .refine((data) => {
    if (data.payment_method === PaymentMethodEnum.Online) {
      return Boolean(data.payment_driver);
    }
    return true;
  }, {
    error: required('payment_driver'),
    path: ['payment_driver']
  });

export const walletWithdrawFormSchema = z.object({
  amount: z.number({error: required('amount')})
    .min(1, {error: () => i18next.t('validation.min.numeric', {
      'attribute': i18next.t('amount'),
      'min': String(1)
    })}),
  user_notes: z.string()
    .max(2000, {error: () => i18next.t('validation.max.string', {
      'attribute': i18next.t('user_note'),
      'max': String(2000)
    })})
    .optional(),
});


export type walletDepositFormSchema = z.infer<typeof walletDepositFormSchema>;
export type walletWithdrawFormSchema = z.infer<typeof walletWithdrawFormSchema>;
