import type { PrecognitionField } from '../types';

type ValidateField = (field: PrecognitionField) => unknown;

/**
 * Blur handler that runs Precognition only when the field has a non-empty value.
 */
export function usePrecognitionBlur(
  field: PrecognitionField,
  value: string | null | undefined,
  validate: ValidateField,
): () => void {
  return () => {
    if ((value ?? '').trim() !== '') {
      validate(field);
    }
  };
}
