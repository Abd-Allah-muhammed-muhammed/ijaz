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
};

const ACCEPT =
  '.jpg,.jpeg,.png,.gif,.pdf,image/jpeg,image/png,image/gif,application/pdf';

const ChatComposer = ({
  content,
  files,
  onContentChange,
  onFilesChange,
  onSend,
  isProcessing = false,
  placeholder,
  disabled = false,
}: ChatComposerProps) => {
  const { t } = useTranslation();
  const fileRef = useRef<HTMLInputElement>(null);

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

  const canSend =
    !disabled &&
    !isProcessing &&
    (content.trim() !== '' || files.length > 0);

  const removeFileAt = (index: number) => {
    onFilesChange(files.filter((_, i) => i !== index));
  };

  const onEnterPress = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (canSend) {
        onSend();
      }
    }
  };

  return (
    <div className="card-footer pt-4">
      {files.length > 0 ? (
        <div className="d-flex flex-wrap gap-3 mb-4">
          {files.map((file, index) => {
            const previewUrl = previewUrls[index];
            const isImage = Boolean(previewUrl);

            return (
              <div
                key={`${file.name}-${file.size}-${file.lastModified}-${index}`}
                className="position-relative border border-gray-300 border-dashed rounded p-2 bg-light"
                style={{ maxWidth: isImage ? 96 : 260 }}
              >
                <button
                  type="button"
                  className="btn btn-icon btn-sm btn-light-danger position-absolute top-0 end-0 translate-middle rounded-circle"
                  style={{ zIndex: 1 }}
                  aria-label={t('remove')}
                  disabled={disabled || isProcessing}
                  onClick={() => removeFileAt(index)}
                >
                  <KTIcon iconName="cross" className="fs-4" />
                </button>

                {isImage && previewUrl ? (
                  <img
                    src={previewUrl}
                    alt={file.name}
                    className="rounded"
                    style={{
                      width: 72,
                      height: 72,
                      objectFit: 'cover',
                      display: 'block',
                    }}
                  />
                ) : (
                  <div className="d-flex align-items-center gap-3 pe-4">
                    <div className="symbol symbol-35px flex-shrink-0">
                      <img
                        alt=""
                        src={appUrl(
                          isPdfFile(file)
                            ? '/media/svg/files/pdf.svg'
                            : '/media/svg/files/doc.svg',
                        )}
                      />
                    </div>
                    <div className="fw-semibold min-w-0">
                      <div className="fs-7 fw-bold text-gray-900 text-truncate" title={file.name}>
                        {file.name}
                      </div>
                      <div className="text-gray-500 fs-8">{formatFileSize(file.size)}</div>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      ) : null}

      <textarea
        className="form-control form-control-flush mb-3"
        rows={1}
        value={content}
        data-kt-element="input"
        placeholder={placeholder ?? t('Type a message')}
        disabled={disabled || isProcessing}
        onChange={(e) => onContentChange(e.target.value)}
        onKeyDown={onEnterPress}
      />

      <div className="d-flex justify-content-end align-items-center gap-1">
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
          onClick={() => {
            if (canSend) {
              onSend();
            }
          }}
          text={t('send')}
        />
      </div>
    </div>
  );
};

export default ChatComposer;
