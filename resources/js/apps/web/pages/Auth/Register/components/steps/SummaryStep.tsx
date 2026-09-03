import { Col, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import type { ProviderType } from '@/shared/types/models';
import type { CategoryOption } from '../../providerSchema';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import StepShell from '../StepShell';
import SummaryRow from './SummaryRow';

export type SummaryStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  providerType: ProviderType | null;
  categoriesOptions: Map<number, CategoryOption>;
};

export default function SummaryStep({
  isCurrent,
  form,
  providerType,
  categoriesOptions,
}: SummaryStepProps) {
  const { t } = useTranslation();

  return (
    <StepShell
      isCurrent={isCurrent}
      headingClassName="pb-8 pb-lg-10"
      heading={<h2 className="fw-bold text-gray-900">{t('summary')}</h2>}
    >
      <div className="mb-0">
        <Row>
          <Col sm={12} md={12}>
            <div className="d-flex flex-column flex-md-row">
              <SummaryRow label={t('name')} value={form.data.name} />
              <SummaryRow label={t('phone')} value={form.data.phone} />
              <SummaryRow label={t('email')} value={form.data.email} />
            </div>
            <div className="d-flex flex-column flex-md-row">
              <SummaryRow label={t('provider_type')} value={providerType?.name} />
              <SummaryRow label={t('iban')} value={form.data.iban} />
            </div>
            <div className="d-flex">
              <SummaryRow
                label={t('about')}
                value={form.data.about}
                multiline
                multilineClassName="text-gray-600 form-control bg-transparent h-auto min-h-100px"
              />
            </div>
          </Col>
          <Col>
            <div className="d-flex">
              <SummaryRow label={t('address')} value={form.data.address} multiline />
            </div>
            <Row>
              <Col sm={12} className="mb-10">
                <span className="fw-bold text-gray-800">{t('categories')}</span>
              </Col>
              {Array.from(categoriesOptions.values()).map((category) => (
                <Col
                  sm={12}
                  md={6}
                  className="d-flex flex-stack mb-5 cursor-pointer"
                  key={`category-${category.id}`}
                >
                  <span className="d-flex align-items-center me-2">
                    <span className="symbol symbol-50px me-6">
                      <span className="symbol-label">
                        <img
                          src={category.icon}
                          className="h-50px align-self-center"
                          alt={category.title}
                        />
                      </span>
                    </span>
                    <span className="d-flex flex-column">
                      <span className="fw-bolder text-gray-800 text-hover-primary fs-5">
                        {category.title}
                      </span>
                      <span className="fs-6 fw-bold text-gray-500" />
                    </span>
                  </span>
                </Col>
              ))}
            </Row>
          </Col>
        </Row>
      </div>
    </StepShell>
  );
}
