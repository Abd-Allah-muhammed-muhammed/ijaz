import {z} from "zod";
import i18n from "@/lang/i18next";

const fileSize = 8; // 8 MB

/** Longest valid KSA mobile input: `00966` + `5` + 8 digits. */
export const SAUDI_PHONE_MAX_LENGTH = 14;

/** Saudi IBAN: `SA` + 22 digits. */
export const SAUDI_IBAN_MAX_LENGTH = 24;

const SAUDI_IBAN_PATTERN = /^SA\d{22}$/;

const KSA_PHONE_PATTERN = /^(?<key>(\+|00)?966|0)?(?<provider>5)(?<digits>\d{8})$/;

export function normalizeSaudiIban(value: string): string {
  return value.toUpperCase().replace(/\s+/g, '');
}

export function isValidSaudiIban(value: string): boolean {
  return SAUDI_IBAN_PATTERN.test(normalizeSaudiIban(value));
}

function isValidKsaPhone(value: string): boolean {
  return KSA_PHONE_PATTERN.test(value);
}

export const formSchema = z.object({
  provider_type_id: z.number({
    error: () => i18n.t('validation.required', {attribute: i18n.t('provider_type')}),
  }),

  requiredFiles: z.object({
    id_image: z.boolean(),
    commercial_record: z.boolean(),
    freelancer_certification: z.boolean(),
    iban_certification: z.boolean(),
  }),

  name: z.string()
    .superRefine((value, ctx) => {
      if (!value || value.trim() === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('name')}),
        });

        return;
      }

      if (value.length < 3) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.min.string', {attribute: i18n.t('name'), min: '3'}),
        });
      }
    }),

  about: z.string()
    .superRefine((value, ctx) => {
      if (!value || value.trim() === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('about')}),
        });
      }
    })
    .optional(),

  email: z.string()
    .superRefine((value, ctx) => {
      if (!value || value.trim() === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('email')}),
        });

        return;
      }

      if (!z.email().safeParse(value).success) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.email', {attribute: i18n.t('email')}),
        });
      }
    }),

  phone: z.string()
    .superRefine((value, ctx) => {
      if (!value || value.trim() === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('phone')}),
        });

        return;
      }

      if (!isValidKsaPhone(value)) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.regex', {attribute: i18n.t('phone')}),
        });
      }
    }),

  address: z.string()
    .superRefine((value, ctx) => {
      if (!value || value.trim() === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('address')}),
        });
      }
    }),

  region_id: z.number({
    error: () => i18n.t('validation.required', {attribute: i18n.t('region')}),
  }),

  city_id: z.number({
    error: () => i18n.t('validation.required', {attribute: i18n.t('city')}),
  }),

  iban: z.string()
    .superRefine((value, ctx) => {
      if (!value || value.trim() === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('iban')}),
        });

        return;
      }

      if (!isValidSaudiIban(value)) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.invalid_saudi_iban', {attribute: i18n.t('iban')}),
        });
      }
    }),

  password: z.string()
    .superRefine((value, ctx) => {
      if (!value || value === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('password')}),
        });

        return;
      }

      if (value.length < 6) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.min.string', {attribute: i18n.t('password'), min: '6'}),
        });
      }
    }),

  password_confirmation: z.string()
    .superRefine((value, ctx) => {
      if (!value || value === '') {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('password_confirmation')}),
        });

        return;
      }

      if (value.length < 6) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.min.string', {attribute: i18n.t('password_confirmation'), min: '6'}),
        });
      }
    }),

  otp: z.string()
    .superRefine((value, ctx) => {
      if (value.length > 4) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.max.string', {attribute: i18n.t('otp'), max: '4'}),
        });
      }
    }),

  categories: z.array(z.object({
    id: z.number({
      error: () => i18n.t('validation.required', {attribute: i18n.t('category')}),
    }),
    skills: z.array(z.number()).nullish(),
  }))
    .superRefine((value, ctx) => {
      if (value.length < 1) {
        ctx.addIssue({
          code: 'custom',
          message: i18n.t('validation.required', {attribute: i18n.t('categories')}),
        });
      }
    }),

  id_image: z.file().optional(),

  commercial_record: z.file().optional(),

  iban_certification: z.file().optional(),

  freelancer_certification: z.file().optional(),

  logo: z.file().optional(),
});


export type Schema = z.infer<typeof formSchema>;

export type CategoryOption = {
  id: number;
  title: string;
  icon: string;
  skills: {
    value: string;
    label: string;
  }[];
}


export type Inputs = {
  provider_type_id: number | null;
  name: string | null;
  about?: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  region_id: number | null;
  city_id: number | null;
  iban: string | null;
  password: string | null;
  password_confirmation: string | null;
  otp: string | null;
  categories: {
    id: number;
    skills: number[];
  }[];
  id_image?: File;
  commercial_record?: File;
  iban_certification?: File;
  freelancer_certification?: File;
  logo: File | undefined;
}

export const accountInformationStepRules = formSchema.pick({
  name: true,
  about: true,
  email: true,
  phone: true,
  address: true,
  region_id: true,
  city_id: true,
  iban: true,
  password: true,
  password_confirmation: true,
}).superRefine((data, ctx) => {
  if (data.password !== data.password_confirmation) {
    ctx.addIssue({
      code: 'custom',
      message: i18n.t('validation.confirmed', {attribute: i18n.t('password')}),
      path: ['password_confirmation'],
    });
  }
});

const filesStepRules = formSchema.pick({
  requiredFiles: true,
  id_image: true,
  commercial_record: true,
  iban_certification: true,
  freelancer_certification: true,
  logo: true,
}).superRefine((data, ctx) => {
  const maxBytes = fileSize * 1024 * 1024;

  const validatePdfFile = (
    file: File | undefined,
    attributeKey: string,
    path: string,
    required: boolean,
  ) => {
    if (required && !file) {
      ctx.addIssue({
        code: 'custom',
        message: i18n.t('validation.required', {attribute: i18n.t(attributeKey)}),
        path: [path],
      });

      return;
    }

    if (!file) {
      return;
    }

    if (file.size > maxBytes) {
      ctx.addIssue({
        code: 'custom',
        message: i18n.t('validation.max.file', {
          attribute: i18n.t(attributeKey),
          max: String(fileSize * 1024),
        }),
        path: [path],
      });
    }

    if (file.type !== 'application/pdf') {
      ctx.addIssue({
        code: 'custom',
        message: i18n.t('validation.mimes', {attribute: i18n.t(attributeKey), values: 'pdf'}),
        path: [path],
      });
    }
  };

  validatePdfFile(data.id_image, 'id_image', 'id_image', data.requiredFiles.id_image);
  validatePdfFile(data.commercial_record, 'commercial_record', 'commercial_record', data.requiredFiles.commercial_record);
  validatePdfFile(data.iban_certification, 'iban_certification', 'iban_certification', data.requiredFiles.iban_certification);
  validatePdfFile(data.freelancer_certification, 'freelancer_certification', 'freelancer_certification', data.requiredFiles.freelancer_certification);

  if (!data.logo) {
    ctx.addIssue({
      code: 'custom',
      message: i18n.t('validation.required', {attribute: i18n.t('logo')}),
      path: ['logo'],
    });

    return;
  }

  if (data.logo.size > maxBytes) {
    ctx.addIssue({
      code: 'custom',
      message: i18n.t('validation.max.file', {
        attribute: i18n.t('logo'),
        max: String(fileSize * 1024),
      }),
      path: ['logo'],
    });
  }

  if (!['image/jpeg', 'image/png'].includes(data.logo.type)) {
    ctx.addIssue({
      code: 'custom',
      message: i18n.t('validation.mimes', {attribute: i18n.t('logo'), values: 'png,jpeg'}),
      path: ['logo'],
    });
  }
});

/**
 * Step metadata for the provider registration sidebar.
 * Store translation KEYS (not i18n.t() results) so labels re-resolve when the locale changes.
 */
export const availableSteps = [
  {
    titleKey: 'account_type',
    descriptionKey: 'select_account_type',
    rules: formSchema.pick({
      provider_type_id: true,
    }),
  },
  {
    titleKey: 'account_information',
    descriptionKey: 'setup_your_account_information',
    requiresPhoneAvailabilityCheck: true,
    rules: accountInformationStepRules,
  },

  {
    titleKey: 'categories & skills',
    descriptionKey: 'select_your_categories & skills',
    rules: formSchema.pick({
      categories: true,
    }),
  },
  {
    titleKey: 'files',
    descriptionKey: 'provide_your_files',
    rules: filesStepRules,
  },
  {
    titleKey: 'summary',
    descriptionKey: 'review_your_information',

  },
  {
    titleKey: 'phone_verification',
    descriptionKey: 'setup_your_phone_verification',
    rules: formSchema.pick({
      otp: true,
    }),
  },

  {
    titleKey: 'completed',
    descriptionKey: 'your_account_is_created',
  },
];
