import { useRef, useState, type ChangeEvent, type RefObject } from 'react';
import {
  prepareLocalImage,
  UPLOAD_LOGO_COMPRESSION,
  type LocalImagePrepareErrorCode,
} from '@/shared/uploads';
import {
  PROFILE_LOGO_ACCEPT,
  PROFILE_LOGO_MAX_BYTES,
  PROFILE_LOGO_MIME_TYPES,
} from '@/apps/provider/pages/Profile/constants';

export type LogoValidationErrorCode = LocalImagePrepareErrorCode;

export function validateLogoMime(
  file: File,
): Extract<LogoValidationErrorCode, 'invalid_type'> | null {
  if (
    !PROFILE_LOGO_MIME_TYPES.includes(
      file.type as (typeof PROFILE_LOGO_MIME_TYPES)[number],
    )
  ) {
    return 'invalid_type';
  }

  return null;
}

export function validateLogoSize(
  file: File,
): Extract<LogoValidationErrorCode, 'too_large'> | null {
  if (file.size > PROFILE_LOGO_MAX_BYTES) {
    return 'too_large';
  }

  return null;
}

/**
 * Mime-check → compress with shared logo profile → size-check.
 * Size is enforced on the *compressed* file so phone-camera originals can exceed 2MB.
 */
export async function prepareLogoFile(file: File): Promise<{
  file: File | null;
  errorCode: LogoValidationErrorCode | null;
  originalSize: number;
  compressedSize: number | null;
}> {
  return prepareLocalImage(file, {
    allowedMimeTypes: PROFILE_LOGO_MIME_TYPES,
    maxBytes: PROFILE_LOGO_MAX_BYTES,
    compressionProfile: UPLOAD_LOGO_COMPRESSION,
  });
}

/** @deprecated Prefer validateLogoMime + prepareLogoFile; kept for simple sync mime/size checks in tests. */
export function validateLogoFile(file: File): LogoValidationErrorCode | null {
  return validateLogoMime(file) ?? validateLogoSize(file);
}

export type UseLogoUploadResult = {
  previewUrl: string | null;
  file: File | null;
  errorCode: LogoValidationErrorCode | null;
  compressing: boolean;
  lastCompression: { originalSize: number; compressedSize: number } | null;
  inputRef: RefObject<HTMLInputElement | null>;
  accept: string;
  openPicker: () => void;
  onInputChange: (event: ChangeEvent<HTMLInputElement>) => Promise<File | undefined>;
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
  const [compressing, setCompressing] = useState(false);
  const [lastCompression, setLastCompression] = useState<{
    originalSize: number;
    compressedSize: number;
  } | null>(null);

  const openPicker = () => {
    inputRef.current?.click();
  };

  const onInputChange = async (
    event: ChangeEvent<HTMLInputElement>,
  ): Promise<File | undefined> => {
    const next = event.target.files?.[0];
    if (!next) {
      return undefined;
    }

    setCompressing(true);
    setErrorCode(null);

    const result = await prepareLogoFile(next);
    setCompressing(false);

    if (result.errorCode || !result.file) {
      setErrorCode(result.errorCode);
      setFile(null);
      setLastCompression(null);
      event.target.value = '';
      return undefined;
    }

    setFile(result.file);
    setLastCompression({
      originalSize: result.originalSize,
      compressedSize: result.compressedSize ?? result.file.size,
    });

    const reader = new FileReader();
    reader.onload = () => {
      if (reader.readyState === 2) {
        setPreviewUrl(reader.result as string);
      }
    };
    reader.readAsDataURL(result.file);

    return result.file;
  };

  const remove = () => {
    setFile(null);
    setErrorCode(null);
    setLastCompression(null);
    setPreviewUrl(initialUrl ?? null);
    if (inputRef.current) {
      inputRef.current.value = '';
    }
  };

  const resetAfterSuccess = () => {
    setFile(null);
    setErrorCode(null);
    setLastCompression(null);
    if (inputRef.current) {
      inputRef.current.value = '';
    }
  };

  return {
    previewUrl,
    file,
    errorCode,
    compressing,
    lastCompression,
    inputRef,
    accept: PROFILE_LOGO_ACCEPT,
    openPicker,
    onInputChange,
    remove,
    resetAfterSuccess,
  };
}
