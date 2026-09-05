import { Fragment } from 'react';
import { Form } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import ImageInput from '@/shared/components/inputs/ImageInput';
import InputError from '@/shared/components/inputs/InputError';
import { PROVIDER_CERTIFICATE_ACCEPT, type Inputs } from '../../providerSchema';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import { useRegistrationUploads } from '../../hooks/registration-uploads-context';
import type { RegistrationUploadField } from '../../registration-upload-constants';
import type { RequiredFilesState } from '../../types';
import StepShell from '../StepShell';

export type FilesStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  requiredFiles: RequiredFilesState;
};

export default function FilesStep({
  isCurrent,
  form,
  requiredFiles,
}: FilesStepProps) {
  const { t } = useTranslation();
  const uploads = useRegistrationUploads();

  const requiredFileNames = (Object.keys(requiredFiles) as Array<keyof RequiredFilesState>).filter(
    (key) => requiredFiles[key],
  );

  const onFileSelected = (field: RegistrationUploadField, file: File) => {
    form.setData(field as keyof Inputs, file);
    form.clearErrors(field as keyof Inputs);
    void uploads.selectAndUpload(field, file);
  };

  return (
    <StepShell
      isCurrent={isCurrent}
      headingClassName="pb-8 pb-lg-10"
      heading={<h2 className="fw-bold text-gray-900">{t('files')}</h2>}
    >
      <Form.Group className="mb-4">
        <Form.Label className="required form-label mb-3" htmlFor="logo">
          {t('logo')}
        </Form.Label>
        <div>
          <ImageInput
            id="logo"
            style={{
              maxHeight: '200px',
            }}
            callback={(event) => {
              if (! event.currentTarget.files || event.currentTarget.files.length === 0) {
                return;
              }
              onFileSelected('logo', event.currentTarget.files[0]);
            }}
          />
        </div>
        <InputError message={form.errors.logo} />
        {uploads.entries.logo ? (
          <div className="text-muted fs-7 mt-1" data-pan="registration-logo-upload-status">
            {uploads.entries.logo.status === 'uploading'
              ? `${t('provider_registration.status_uploading')} ${uploads.entries.logo.progress}%`
              : null}
            {uploads.entries.logo.status === 'done' ? t('provider_registration.status_done') : null}
            {uploads.entries.logo.status === 'failed' ? t('provider_registration.status_failed') : null}
          </div>
        ) : null}
      </Form.Group>

      {requiredFileNames.map((fileName, index) => (
        <Fragment key={`file-${index}`}>
          <Form.Group className="mb-10">
            <Form.Label className="required form-label mb-3" htmlFor={`file-${fileName}`}>
              {t(fileName)}
            </Form.Label>
            <input
              className="form-control"
              id={`file-${fileName}`}
              type="file"
              accept={PROVIDER_CERTIFICATE_ACCEPT}
              onChange={(event) => {
                if (! event.currentTarget.files || event.currentTarget.files.length === 0) {
                  return;
                }
                onFileSelected(fileName as RegistrationUploadField, event.currentTarget.files[0]);
              }}
            />
            <InputError message={form.errors[fileName as keyof Inputs]} />
            {uploads.entries[fileName as RegistrationUploadField]?.fileName ? (
              <div className="text-muted fs-7 mt-1">
                {uploads.entries[fileName as RegistrationUploadField]?.fileName}
                {' · '}
                {uploads.entries[fileName as RegistrationUploadField]?.status === 'done'
                  ? t('provider_registration.status_done')
                  : null}
                {uploads.entries[fileName as RegistrationUploadField]?.status === 'uploading'
                  ? `${uploads.entries[fileName as RegistrationUploadField]?.progress ?? 0}%`
                  : null}
                {uploads.entries[fileName as RegistrationUploadField]?.status === 'failed'
                  ? t('provider_registration.status_failed')
                  : null}
              </div>
            ) : null}
          </Form.Group>
        </Fragment>
      ))}
    </StepShell>
  );
}
