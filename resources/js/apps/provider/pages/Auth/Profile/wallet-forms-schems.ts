import {z} from "zod";

import i18next from "@/lang/i18next";
import {PaymentMethodEnum} from "@/Enums/Payment";

const fileSize = 2; // 5 MB

export const walletDepositFormSchema = z.object({
  amount: z.number(i18next.t('validation.required', {attribute: i18next.t('amount')}))
    .min(1, i18next.t('validation.min.numeric', {
      'attribute': i18next.t('amount'),
      'max': String(1)
    })),
  payment_method: z.string(i18next.t('validation.required', {attribute: i18next.t('payment_method')}))
    .nonempty(),
  payment_driver: z.string().optional(),
  user_notes: z.string()
    .max(2000, i18next.t('validation.max.string', {
      'attribute': i18next.t('user_note'),
      'max': String(2000)
    }))
    .optional(),
  transaction_image: z.file(i18next.t('validation.required', {attribute: i18next.t('transaction_image')}))
    .max(fileSize * 1024 * 102, i18next.t('validation.max.file', {
      'attribute': i18next.t('transaction_image'),
      'max': fileSize + 'MB'
    }))
    .mime(['image/jpeg', 'image/png'], i18next.t('validation.mimes', {
      'attribute': i18next.t('transaction_image'),
      'values': 'png,jpeg'
    }))
    .optional(),
})
  .refine((data) => {
    if (data.payment_method === PaymentMethodEnum.Offline) {
      return data.transaction_image !== undefined;
    }
    return true;
  }, {
    message: i18next.t('validation.required', {attribute: i18next.t('transaction_image')}),
    path: ['transaction_image']
  })
  .refine((data) => {
    if (data.payment_method === PaymentMethodEnum.Online) {
      return Boolean(data.payment_driver);
    }
    return true;
  }, {
    message: i18next.t('validation.required', {attribute: i18next.t('payment_driver')}),
    path: ['payment_driver']
  });

export const walletWithdrawFormSchema = z.object({
  amount: z.number(i18next.t('validation.required', {attribute: i18next.t('amount')}))
    .min(1, i18next.t('validation.min.numeric', {
      'attribute': i18next.t('amount'),
      'max': String(1)
    })),
  user_notes: z.string()
    .max(2000, i18next.t('validation.max.string', {
      'attribute': i18next.t('user_note'),
      'max': String(2000)
    }))
    .optional(),
});


export type walletDepositFormSchema = z.infer<typeof walletDepositFormSchema>;
export type walletWithdrawFormSchema = z.infer<typeof walletWithdrawFormSchema>;

