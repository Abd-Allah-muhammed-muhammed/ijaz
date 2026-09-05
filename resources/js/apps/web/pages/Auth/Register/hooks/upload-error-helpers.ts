import type { RegistrationUploadField } from '../registration-upload-constants';

const FILE_FIELD_KEYS: RegistrationUploadField[] = [
  'logo',
  'id_image',
  'commercial_record',
  'iban_certification',
  'freelancer_certification',
  'license_to_practice_law',
];

/** Testable helper mirroring use-registration-advance expired-field extraction. */
export function extractExpiredUploadFieldsForTest(
  errors: Record<string, string>,
): RegistrationUploadField[] {
  const fields: RegistrationUploadField[] = [];

  Object.keys(errors).forEach((key) => {
    const match = key.match(/^uploads\.(.+)$/);
    const field = (match?.[1] ?? (FILE_FIELD_KEYS.includes(key as RegistrationUploadField) ? key : null)) as RegistrationUploadField | null;

    if (field && FILE_FIELD_KEYS.includes(field) && ! fields.includes(field)) {
      fields.push(field);
    }
  });

  return fields;
}
