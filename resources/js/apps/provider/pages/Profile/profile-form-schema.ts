import z from 'zod';
import i18next from '@/lang/i18next';
import {
  PROFILE_LOGO_MAX_BYTES,
  PROFILE_LOGO_MAX_LABEL,
  PROFILE_LOGO_MIME_TYPES,
} from '@/apps/provider/pages/Profile/constants';

const pdfMaxBytes = 5 * 1024 * 1024;

export const profileFormSchema = z
  .object({
    id: z.coerce.number().optional(),
    provider_type_id: z.number(
      i18next.t('validation.required', { attribute: i18next.t('provider_type') }),
    ),
    requiredFiles: z.object({
      id_image: z.boolean(),
      commercial_record: z.boolean(),
      freelancer_certification: z.boolean(),
      iban_certification: z.boolean(),
      license_to_practice_law: z.boolean().optional(),
    }),
    name: z
      .string(i18next.t('validation.required', { attribute: i18next.t('name') }))
      .nonempty(i18next.t('validation.required', { attribute: i18next.t('name') }))
      .min(
        3,
        i18next.t('validation.min.string', {
          attribute: i18next.t('name'),
          min: '3',
        }),
      ),
    about: z
      .string(i18next.t('validation.required', { attribute: i18next.t('about') }))
      .nonempty(i18next.t('validation.required', { attribute: i18next.t('about') })),
    email: z
      .email(i18next.t('validation.email', { attribute: i18next.t('email') }))
      .nonempty(i18next.t('validation.required', { attribute: i18next.t('email') })),
    phone: z
      .string(i18next.t('validation.required', { attribute: i18next.t('phone') }))
      .nonempty(i18next.t('validation.required', { attribute: i18next.t('phone') }))
      .regex(
        new RegExp('^(?<key>(\\+|00)?966|0)?(?<provider>5)(?<digits>\\d{8})$'),
        i18next.t('validation.regex', { attribute: i18next.t('phone') }),
      ),
    address: z
      .string(i18next.t('validation.required', { attribute: i18next.t('address') }))
      .nonempty(i18next.t('validation.required', { attribute: i18next.t('address') })),
    region_id: z.number(
      i18next.t('validation.required', { attribute: i18next.t('region') }),
    ),
    city_id: z.number(
      i18next.t('validation.required', { attribute: i18next.t('city') }),
    ),
    iban: z
      .string(i18next.t('validation.required', { attribute: i18next.t('iban') }))
      .nonempty(i18next.t('validation.required', { attribute: i18next.t('iban') })),
    password: z
      .string(i18next.t('validation.required', { attribute: i18next.t('password') }))
      .nullish(),
    password_confirmation: z
      .string(
        i18next.t('validation.required', {
          attribute: i18next.t('password_confirmation'),
        }),
      )
      .nullish(),
    categories: z
      .array(
        z.object({
          id: z.number(
            i18next.t('validation.required', { attribute: i18next.t('category') }),
          ),
          skills: z.array(z.number()).nullish(),
        }),
      )
      .min(
        1,
        i18next.t('validation.required', { attribute: i18next.t('categories') }),
      ),
    id_image: z
      .file()
      .max(
        pdfMaxBytes,
        i18next.t('validation.max.file', {
          attribute: i18next.t('id_image'),
          max: '5MB',
        }),
      )
      .mime(
        ['application/pdf'],
        i18next.t('validation.mimes', {
          attribute: i18next.t('id_image'),
          values: 'pdf',
        }),
      )
      .optional(),
    commercial_record: z
      .file()
      .max(
        pdfMaxBytes,
        i18next.t('validation.max.file', {
          attribute: i18next.t('commercial_record'),
          max: '5MB',
        }),
      )
      .mime(
        ['application/pdf'],
        i18next.t('validation.mimes', {
          attribute: i18next.t('commercial_record'),
          values: 'pdf',
        }),
      )
      .optional(),
    iban_certification: z
      .file()
      .max(
        pdfMaxBytes,
        i18next.t('validation.max.file', {
          attribute: i18next.t('iban_certification'),
          max: '5MB',
        }),
      )
      .mime(
        ['application/pdf'],
        i18next.t('validation.mimes', {
          attribute: i18next.t('iban_certification'),
          values: 'pdf',
        }),
      )
      .optional(),
    freelancer_certification: z
      .file()
      .max(
        pdfMaxBytes,
        i18next.t('validation.max.file', {
          attribute: i18next.t('freelancer_certification'),
          max: '5MB',
        }),
      )
      .mime(
        ['application/pdf'],
        i18next.t('validation.mimes', {
          attribute: i18next.t('freelancer_certification'),
          values: 'pdf',
        }),
      )
      .optional(),
    license_to_practice_law: z
      .file()
      .max(
        pdfMaxBytes,
        i18next.t('validation.max.file', {
          attribute: i18next.t('license_to_practice_law'),
          max: '5MB',
        }),
      )
      .mime(
        ['application/pdf'],
        i18next.t('validation.mimes', {
          attribute: i18next.t('license_to_practice_law'),
          values: 'pdf',
        }),
      )
      .optional(),
    logo: z
      .file(i18next.t('validation.required', { attribute: i18next.t('logo') }))
      .max(
        PROFILE_LOGO_MAX_BYTES,
        i18next.t('validation.max.file', {
          attribute: i18next.t('logo'),
          max: PROFILE_LOGO_MAX_LABEL,
        }),
      )
      .mime(
        [...PROFILE_LOGO_MIME_TYPES],
        i18next.t('validation.mimes', {
          attribute: i18next.t('logo'),
          values: 'png,jpeg',
        }),
      )
      .optional(),
  })
  .refine(
    (data) => {
      if (data.id) {
        return true;
      }
      return !!data.logo;
    },
    {
      message: i18next.t('validation.required', { attribute: i18next.t('logo') }),
      path: ['logo'],
    },
  )
  .refine(
    (data) => {
      if (data.id) {
        return true;
      }
      return Boolean(data.password);
    },
    {
      message: i18next.t('validation.required', { attribute: i18next.t('password') }),
      path: ['password'],
    },
  )
  .refine(
    (data) => {
      if (data.id && !data.password) {
        return true;
      }
      return data.password === data.password_confirmation;
    },
    {
      message: i18next.t('validation.confirmed', {
        attribute: i18next.t('password_confirmation'),
      }),
      path: ['password_confirmation'],
    },
  );
