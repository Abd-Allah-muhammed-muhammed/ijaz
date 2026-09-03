import { Col, Row } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import InputError from '@/shared/components/inputs/InputError';
import { CategoryPicker } from '@/shared/components/categories/category-picker';
import type { CategoryOption } from '../../providerSchema';
import type { RegistrationForm } from '../../hooks/use-registration-form';
import StepShell from '../StepShell';

export type CategoriesStepProps = {
  isCurrent: boolean;
  form: RegistrationForm;
  providerTypeId: number | string | undefined;
  onCategoriesChange: (options: Map<number, CategoryOption>) => void;
};

export default function CategoriesStep({
  isCurrent,
  form,
  providerTypeId,
  onCategoriesChange,
}: CategoriesStepProps) {
  const { t } = useTranslation();

  return (
    <StepShell isCurrent={isCurrent}>
      <Row className="mb-7 fv-row">
        <Col sm={12} className="mb-5">
          <h4 className="fw-bold text-gray-900">{t('categories')}</h4>
          <InputError message={form.errors.categories} />
        </Col>
        <Col sm={12}>
          <CategoryPicker
            provider_type_id={providerTypeId}
            value={form.data.categories}
            onChange={(selected) => {
              const next = new Map<number, CategoryOption>();
              selected.forEach((item) => {
                next.set(item.id, {
                  id: item.id,
                  title: item.title,
                  icon: item.icon,
                  skills: [],
                });
              });
              onCategoriesChange(next);
              form.setData(
                'categories',
                selected.map((item) => ({
                  id: item.id,
                  skills: [],
                })),
              );
            }}
          />
        </Col>
      </Row>
    </StepShell>
  );
}
