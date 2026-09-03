import { Fragment } from 'react';
import { Form } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import ImageInput from '@/shared/components/inputs/ImageInput';
import InputError from '@/shared/components/inputs/InputError';
import { PROVIDER_CERTIFICATE_ACCEPT, type Inputs } from '../../providerSchema';
import type { RegistrationForm } from '../../hooks/use-registration-form';
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

  const requiredFileNames = (Object.keys(requiredFiles) as Array<keyof RequiredFilesState>).filter(
    (key) => requiredFiles[key],
  );

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
              form.setData('logo', event.currentTarget.files[0]);
            }}
          />
        </div>
        <InputError message={form.errors.logo} />
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
                form.setData(fileName as keyof Inputs, event.currentTarget.files[0]);
              }}
            />
            <InputError message={form.errors[fileName as keyof Inputs]} />
          </Form.Group>
        </Fragment>
      ))}
    </StepShell>
  );
}
