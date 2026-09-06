import { useTranslation } from 'react-i18next';
import InputError from '@/shared/components/inputs/InputError';
import {
  SectionCard,
  SECONDARY_BUTTON_CLASS,
} from '@/shared/components/ui';
import {
  PROFILE_CARD_CLASS,
  PROFILE_LOGO_MAX_LABEL,
  PROFILE_LOGO_THUMB_CLASS,
} from '@/apps/provider/pages/Profile/constants';
import { useLogoUpload } from '@/apps/provider/pages/Profile/hooks/use-logo-upload';

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
        : logo.errorCode === 'compression_failed'
          ? t('something went wrong')
          : serverError;

  return (
    <SectionCard title={t('logo')} className={PROFILE_CARD_CLASS}>
      <div className="d-flex align-items-center gap-3 flex-wrap">
        <div className={PROFILE_LOGO_THUMB_CLASS}>
          {logo.previewUrl ? (
            <img
              src={logo.previewUrl}
              alt={t('provider_logo_alt', { name: providerName })}
              className="w-100 h-100 object-fit-cover"
            />
          ) : (
            <span className="symbol-label fs-3 fw-bold text-primary">
              {(providerName ?? '').charAt(0)}
            </span>
          )}
        </div>

        <div className="d-flex flex-column gap-2 min-w-0">
          <p className="text-muted fs-7 mb-0">
            {t('logo_upload_hint', { max: PROFILE_LOGO_MAX_LABEL })}
            {logo.compressing ? ` — ${t('provider_registration.status_compressing')}` : null}
          </p>
          <div className="d-flex flex-wrap gap-2">
            <button
              type="button"
              className={SECONDARY_BUTTON_CLASS}
              onClick={logo.openPicker}
              disabled={logo.compressing}
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
              disabled={!logo.file || logo.compressing}
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
        onChange={async (event) => {
          const prepared = await logo.onInputChange(event);
          onFileChange(prepared);
        }}
      />
    </SectionCard>
  );
}
