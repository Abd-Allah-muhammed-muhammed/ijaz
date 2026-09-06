import { useRef, useState, type ChangeEvent, type RefObject } from 'react';
import {
  PROFILE_LOGO_ACCEPT,
  PROFILE_LOGO_MAX_BYTES,
  PROFILE_LOGO_MIME_TYPES,
} from '@/apps/provider/pages/Profile/constants';

export type LogoValidationErrorCode = 'too_large' | 'invalid_type';

/** Pure size/mime check — source of truth matches server `max:2048`. */
export function validateLogoFile(file: File): LogoValidationErrorCode | null {
  if (file.size > PROFILE_LOGO_MAX_BYTES) {
    return 'too_large';
  }

  if (
    !PROFILE_LOGO_MIME_TYPES.includes(
      file.type as (typeof PROFILE_LOGO_MIME_TYPES)[number],
    )
  ) {
    return 'invalid_type';
  }

  return null;
}

export type UseLogoUploadResult = {
  previewUrl: string | null;
  file: File | null;
  errorCode: LogoValidationErrorCode | null;
  inputRef: RefObject<HTMLInputElement | null>;
  accept: string;
  openPicker: () => void;
  onInputChange: (event: ChangeEvent<HTMLInputElement>) => void;
  remove: () => void;
  resetAfterSuccess: () => void;
};

export function useLogoUpload(
  initialUrl: string | null | undefined,
): UseLogoUploadResult {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(
    initialUrl ?? null,
  );
  const [file, setFile] = useState<File | null>(null);
  const [errorCode, setErrorCode] = useState<LogoValidationErrorCode | null>(
    null,
  );

  const openPicker = () => {
    inputRef.current?.click();
  };

  const onInputChange = (event: ChangeEvent<HTMLInputElement>) => {
    const next = event.target.files?.[0];
    if (!next) {
      return;
    }

    const code = validateLogoFile(next);
    if (code) {
      setErrorCode(code);
      setFile(null);
      event.target.value = '';
      return;
    }

    setErrorCode(null);
    setFile(next);
    const reader = new FileReader();
    reader.onload = () => {
      if (reader.readyState === 2) {
        setPreviewUrl(reader.result as string);
      }
    };
    reader.readAsDataURL(next);
  };

  const remove = () => {
    setFile(null);
    setErrorCode(null);
    setPreviewUrl(initialUrl ?? null);
    if (inputRef.current) {
      inputRef.current.value = '';
    }
  };

  const resetAfterSuccess = () => {
    setFile(null);
    setErrorCode(null);
    if (inputRef.current) {
      inputRef.current.value = '';
    }
  };

  return {
    previewUrl,
    file,
    errorCode,
    inputRef,
    accept: PROFILE_LOGO_ACCEPT,
    openPicker,
    onInputChange,
    remove,
    resetAfterSuccess,
  };
}
