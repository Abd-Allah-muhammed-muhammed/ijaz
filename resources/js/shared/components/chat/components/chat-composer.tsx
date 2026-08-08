import React, { useEffect, useMemo, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPaperclip } from '@fortawesome/free-solid-svg-icons';
import ActionButton from '@/shared/components/action-button';
import { KTIcon } from '@/vendor/metronic/helpers';
import { url as appUrl } from '@/shared/helpers/general';
import {
  formatFileSize,
  isImageFile,
  isPdfFile,
} from '@/shared/components/chat/components/chat-attachment-utils';

export type ChatComposerProps = {
  content: string;
  files: File[];
  onContentChange: (content: string) => void;
  onFilesChange: (files: File[]) => void;
  onSend: () => void;
  isProcessing?: boolean;
  placeholder?: string;
  disabled?: boolean;
  /** Indexes of files that failed validation (e.g. too large) — red border. */
  errorFileIndexes?: number[];
};

const ACCEPT =
  '.jpg,.jpeg,.png,.gif,.pdf,image/jpeg,image/png,image/gif,application/pdf';

const PREVIEW_IMAGE_SIZE = 72;
const PREVIEW_FILE_WIDTH = 200;

const ChatComposer = ({
  content,
  files,
  onContentChange,
  onFilesChange,
  onSend,
  isProcessing = false,
  placeholder,
  disabled = false,
  errorFileIndexes = [],
}: ChatComposerProps) => {
  const { t } = useTranslation();
  const fileRef = useRef<HTMLInputElement>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const composerRootRef = useRef<HTMLDivElement>(null);
  /** Set when this composer initiates a send; cleared after post-send focus restore. */
  const restoreFocusAfterSendRef = useRef(false);
  const errorIndexSet = useMemo(
    () => new Set(errorFileIndexes),
    [errorFileIndexes],
  );

  const previewUrls = useMemo(() => {
    return files.map((file) =>
      isImageFile(file) ? URL.createObjectURL(file) : null,
    );
  }, [files]);

  useEffect(() => {
    return () => {
      previewUrls.forEach((previewUrl) => {
        if (previewUrl) {
          URL.revokeObjectURL(previewUrl);
        }
      });
    };
  }, [previewUrls]);

  /**
   * Cause: `disabled={isProcessing}` blurs the textarea (browsers drop focus from
   * disabled controls). Clearing `content` / file previews does not remount it.
   * After send finishes, restore focus only if nothing outside the composer took it
   * (e.g. user opened search mid-flight) — otherwise we would steal focus.
   */
  useEffect(() => {
    if (isProcessing || !restoreFocusAfterSendRef.current) {
      return;
    }

    restoreFocusAfterSendRef.current = false;

    const active = document.activeElement;
    const root = composerRootRef.current;
    const safeToRestore =
      !active ||
      active === document.body ||
      active === textareaRef.current ||
      (root != null && root.contains(active));

    if (safeToRestore) {
      textareaRef.current?.focus();
    }
  }, [isProcessing]);

  const canSend =
    !disabled &&
    !isProcessing &&
    (content.trim() !== '' || files.length > 0);

  const requestSend = () => {
    if (!canSend) {
      return;
    }
    restoreFocusAfterSendRef.current = true;
    onSend();
  };

  const removeFileAt = (index: number) => {
    onFilesChange(files.filter((_, i) => i !== index));
  };

  const onEnterPress = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      requestSend();
    }
  };

  return (
    <div ref={composerRootRef} className="card-footer pt-4 min-w-0 w-100">
      {files.length > 0 ? (
        <div className="d-flex flex-wrap gap-2 mb-4 w-100 min-w-0">
          {files.map((file, index) => {
            const previewUrl = previewUrls[index];
            const isImage = Boolean(previewUrl);
            const hasError = errorIndexSet.has(index);

            return (
              <div
                key={`${file.name}-${file.size}-${file.lastModified}-${index}`}
                className={`position-relative border border-dashed rounded p-2 bg-light overflow-hidden flex-shrink-0 ${
                  hasError ? 'border-danger' : 'border-gray-300'
                }`}
                style={{
                  width: isImage ? PREVIEW_IMAGE_SIZE + 16 : PREVIEW_FILE_WIDTH,
                  maxWidth: '100%',
                  boxShadow: hasError ? '0 0 0 1px var(--bs-danger)' : undefined,
                }}
              >
                <button
                  type="button"
                  className="btn btn-icon btn-sm btn-light-danger position-absolute top-0 end-0 m-1 rounded-circle"
                  style={{ zIndex: 1, width: 22, height: 22 }}
                  aria-label={t('remove')}
                  disabled={disabled || isProcessing}
                  onClick={() => removeFileAt(index)}
                >
                  <KTIcon iconName="cross" className="fs-5" />
                </button>

                {isImage && previewUrl ? (
                  <img
                    src={previewUrl}
                    alt={file.name}
                    className="rounded"
                    style={{
                      width: PREVIEW_IMAGE_SIZE,
                      height: PREVIEW_IMAGE_SIZE,
                      objectFit: 'cover',
                      display: 'block',
                    }}
                  />
                ) : (
                  <div className="d-flex align-items-center gap-2 pe-4 min-w-0">
                    <div className="symbol symbol-30px flex-shrink-0">
                      <img
                        alt=""
                        src={appUrl(
                          isPdfFile(file)
                            ? '/media/svg/files/pdf.svg'
                            : '/media/svg/files/doc.svg',
                        )}
                      />
                    </div>
                    <div className="fw-semibold min-w-0 flex-grow-1 overflow-hidden">
                      <div
                        className="fs-7 fw-bold text-gray-900 text-truncate"
                        title={file.name}
                      >
                        {file.name}
                      </div>
                      <div className="text-gray-500 fs-8 text-truncate">
                        {formatFileSize(file.size)}
                      </div>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      ) : null}

      <textarea
        ref={textareaRef}
        className="form-control form-control-flush mb-3"
        rows={1}
        value={content}
        data-kt-element="input"
        placeholder={placeholder ?? t('Type a message')}
        disabled={disabled || isProcessing}
        onChange={(e) => onContentChange(e.target.value)}
        onKeyDown={onEnterPress}
      />

      <div className="d-flex justify-content-end align-items-center flex-wrap gap-1">
        <input
          type="file"
          className="d-none"
          ref={fileRef}
          accept={ACCEPT}
          multiple
          disabled={disabled || isProcessing}
          onChange={(e) => {
            const picked = Array.from(e.target.files ?? []);
            if (picked.length) {
              onFilesChange([...files, ...picked]);
            }
            e.target.value = '';
          }}
        />

        <button
          type="button"
          className="btn btn-icon btn-light-primary"
          disabled={disabled || isProcessing}
          aria-label="Attach files"
          onClick={() => {
            if (fileRef.current?.disabled) {
              return;
            }
            fileRef.current?.click();
          }}
        >
          <FontAwesomeIcon icon={faPaperclip} />
        </button>

        {files.length > 0 ? (
          <button
            type="button"
            className="btn btn-light"
            disabled={disabled || isProcessing}
            onClick={() => onFilesChange([])}
          >
            {t('remove')}
          </button>
        ) : null}

        <ActionButton
          isProcessing={isProcessing}
          type="button"
          onClick={requestSend}
          text={t('send')}
        />
      </div>
    </div>
  );
};

export default ChatComposer;
