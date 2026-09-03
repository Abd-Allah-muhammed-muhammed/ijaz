import { Col } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import InputError from '@/shared/components/inputs/InputError';
import GeneralController from '@/actions/App/Http/Controllers/Frontend/GeneralController';
import { ProviderTypeFilesEnum } from '@/Enums/Enums';
import type { ProviderType } from '@/shared/types/models';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import type { RequiredFilesState } from '../../types';
import StepShell from '../StepShell';

export type AccountTypeStepProps = {
  isCurrent: boolean;
  types: ProviderType[];
  form: RegistrationForm;
  onProviderTypeSelected: (type: ProviderType, files: RequiredFilesState) => void;
};

export default function AccountTypeStep({
  isCurrent,
  types,
  form,
  onProviderTypeSelected,
}: AccountTypeStepProps) {
  const { t } = useTranslation();

  return (
    <StepShell
      isCurrent={isCurrent}
      headingClassName="pb-10 pb-lg-15"
      heading={(
        <>
          <h2 className="fw-bold d-flex align-items-center text-gray-900">
            {t('select_account_type')}
            <span
              className="ms-1"
              data-bs-toggle="tooltip"
              title="Billing is issued based on your selected account typ"
            >
              <i className="ki-duotone ki-information-5 text-gray-500 fs-6">
                <span className="path1" />
                <span className="path2" />
                <span className="path3" />
              </i>
            </span>
          </h2>
          <div className="text-muted fw-semibold fs-6">
            {t('if_you_need_more_info,_please_check_out')}
            <a href={GeneralController.index().url} className="link-primary fw-bold mx-2">
              {t('help_page')}
            </a>
            .
          </div>
          <div>
            <InputError message={form.errors.provider_type_id} />
          </div>
        </>
      )}
    >
      <div className="fv-row">
        {types.map((type) => (
          <Col lg={12} key={`type-${type.id}`} className="mb-6">
            <input
              type="radio"
              className="btn-check"
              checked={form.data.provider_type_id == type.id}
              onChange={() => {
                const files = {} as RequiredFilesState;
                Object.values(ProviderTypeFilesEnum).forEach((value) => {
                  files[value] = type.files[value] || false;
                });
                form.setData('provider_type_id', type.id as number);
                onProviderTypeSelected(type, files);
              }}
              id={`type-${type.id}`}
            />
            <label
              className="btn btn-outline btn-outline-dashed btn-active-light-primary p-7 d-flex align-items-start gap-3"
              htmlFor={`type-${type.id}`}
              style={{ height: '100%', cursor: 'pointer' }}
            >
              <img src={type.image} height={80} width={80} alt={type.name} />
              <span className="d-block fw-semibold text-start">
                <span className="text-gray-900 fw-bold d-block fs-4 mb-2">{type.name}</span>
                <span className="fs-6">
                  <p
                    style={{ lineBreak: 'loose', maxHeight: '150px' }}
                    className="m-0 overflow-y-auto"
                  >
                    {type.description}
                  </p>
                </span>
              </span>
            </label>
          </Col>
        ))}
      </div>
    </StepShell>
  );
}
