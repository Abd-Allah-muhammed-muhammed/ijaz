import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';
import InputError from '@/shared/components/inputs/InputError';
import {
  SectionCard,
  SECONDARY_BUTTON_CLASS,
} from '@/shared/components/ui';
import {
  SelectCategoryModal,
  type Data as SelectCategoryModalData,
} from '@/shared/components/categories/category-selector/select-category-modal';
import { PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX } from '@/apps/provider/pages/Profile/constants';
import type { ProfileCategorySelection } from '@/apps/provider/pages/Profile/types';

export type CategoriesCardProps = {
  providerTypeId: number | null;
  selectingCategory: ProfileCategorySelection[];
  error?: string;
  onMergeFromModal: (data: SelectCategoryModalData[]) => void;
  onRemoveCategory: (categoryId: number) => void;
};

export default function CategoriesCard({
  providerTypeId,
  selectingCategory,
  error,
  onMergeFromModal,
  onRemoveCategory,
}: CategoriesCardProps) {
  const { t } = useTranslation();
  const T = t as (key: string) => string;
  const [showModal, setShowModal] = useState(false);

  return (
    <SectionCard
      className="mb-5"
      title={T('categories & skills')}
      headerExtra={
        <button
          type="button"
          className={SECONDARY_BUTTON_CLASS}
          disabled={!providerTypeId}
          onClick={() => setShowModal(true)}
          aria-label={T('manage_categories')}
        >
          {T('manage_categories')}
        </button>
      }
    >
      <InputError message={error} />
      <div
        className="overflow-auto pe-1"
        style={{ maxHeight: PROFILE_CATEGORIES_SCROLL_MAX_HEIGHT_PX }}
      >
        {selectingCategory.length === 0 ? (
          <p className="text-muted fs-7 mb-0">{T('no_categories_selected')}</p>
        ) : (
          <ul className="list-unstyled mb-0 d-flex flex-column gap-3">
            {selectingCategory.map((item) => (
              <li
                key={`category-${item.category.id}`}
                className="d-flex align-items-start justify-content-between gap-3"
              >
                <div className="d-flex align-items-center gap-3 min-w-0">
                  <span className="symbol symbol-40px flex-shrink-0">
                    <span className="symbol-label bg-light-primary">
                      {item.category.icon ? (
                        <img
                          src={item.category.icon}
                          alt=""
                          className="w-100 h-100"
                        />
                      ) : null}
                    </span>
                  </span>
                  <div className="min-w-0">
                    <div className="fw-bolder fs-6 text-gray-900 text-break">
                      {item.category.title}
                    </div>
                    <div className="d-flex flex-wrap gap-2 mt-1">
                      {item.skills.map((skill) => (
                        <span
                          className="badge badge-light-primary"
                          key={`skill-${skill.value}`}
                        >
                          {skill.label}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  className="btn btn-sm btn-icon btn-light-danger"
                  aria-label={T('remove')}
                  onClick={() => onRemoveCategory(item.category.id as number)}
                >
                  <KTIcon iconName="cross" className="fs-2" />
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>

      <SelectCategoryModal
        show={showModal}
        handleClose={() => setShowModal(false)}
        provider_type_id={providerTypeId as unknown as string}
        submitCallback={(data) => {
          onMergeFromModal(data);
          setShowModal(false);
        }}
      />
    </SectionCard>
  );
}
