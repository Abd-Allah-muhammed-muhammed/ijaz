import { useTranslation } from 'react-i18next';
import InputError from '@/shared/components/inputs/InputError';
import {
  SectionCard,
  SECONDARY_BUTTON_CLASS,
} from '@/shared/components/ui';
import {
  PROFILE_LOGO_MAX_LABEL,
  PROFILE_LOGO_THUMB_CLASS,
} from '@/apps/provider/pages/Profile/constants';
import {
  useLogoUpload,
  validateLogoFile,
} from '@/apps/provider/pages/Profile/hooks/use-logo-upload';

export type LogoCardProps = {
  providerName: string;
  initialUrl: string | null | undefined;
  serverError?: string;
  onFileChange: (file: File | undefined) => void;
};

export default function LogoCard({
  providerName,
  initialUrl,
  serverError,
  onFileChange,
}: LogoCardProps) {
  const { t } = useTranslation();
  const logo = useLogoUpload(initialUrl);

  const errorMessage =
    logo.errorCode === 'too_large'
      ? t('validation.max.file', {
          attribute: t('logo'),
          max: PROFILE_LOGO_MAX_LABEL,
        })
      : logo.errorCode === 'invalid_type'
        ? t('validation.mimes', {
            attribute: t('logo'),
            values: 'png,jpeg',
          })
        : serverError;

  return (
    <SectionCard title={t('logo')} className="mb-5">
      <div className="d-flex flex-column flex-sm-row align-items-sm-center gap-4">
        <div className={PROFILE_LOGO_THUMB_CLASS}>
          {logo.previewUrl ? (
            <img
              src={logo.previewUrl}
              alt={t('provider_logo_alt', { name: providerName })}
              className="w-100 h-100 object-fit-contain"
            />
          ) : (
            <span className="symbol-label fs-2 fw-bold text-primary">
              {(providerName ?? '').charAt(0)}
            </span>
          )}
        </div>

        <div className="d-flex flex-column gap-2">
          <p className="text-muted fs-7 mb-0">
            {t('logo_upload_hint', { max: PROFILE_LOGO_MAX_LABEL })}
          </p>
          <div className="d-flex flex-wrap gap-2">
            <button
              type="button"
              className={SECONDARY_BUTTON_CLASS}
              onClick={logo.openPicker}
              aria-label={t('change_logo')}
            >
              {t('change_logo')}
            </button>
            <button
              type="button"
              className={SECONDARY_BUTTON_CLASS}
              onClick={() => {
                logo.remove();
                onFileChange(undefined);
              }}
              disabled={!logo.file}
              aria-label={t('remove')}
            >
              {t('remove')}
            </button>
          </div>
          <InputError message={errorMessage} />
        </div>
      </div>

      <input
        ref={logo.inputRef}
        type="file"
        className="d-none"
        accept={logo.accept}
        onChange={(event) => {
          const next = event.target.files?.[0];
          logo.onInputChange(event);
          if (!next || validateLogoFile(next)) {
            onFileChange(undefined);
            return;
          }
          onFileChange(next);
        }}
      />
    </SectionCard>
  );
}
