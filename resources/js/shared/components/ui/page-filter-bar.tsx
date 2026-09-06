import { useEffect, useState } from 'react';
import { KTIcon } from '@/vendor/metronic/helpers';
import {
  clearSearchFilterValue,
  shouldShowSearchClearButton,
} from './page-filter-bar-utils';
import {
  PAGE_FILTER_BAR_CLASS,
  PAGE_FILTER_CONTROLS_CLASS,
  PAGE_FILTER_DATE_CLASS,
  PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS,
  PAGE_FILTER_DATE_LABEL_CLASS,
  PAGE_FILTER_SEARCH_CLEAR_BUTTON_CLASS,
  PAGE_FILTER_SEARCH_COLUMN_CLASS,
  PAGE_FILTER_SEARCH_FIELD_CLASS,
  PAGE_FILTER_SEARCH_ICON_CLASS,
  PAGE_FILTER_SEARCH_INPUT_CLASS,
  PAGE_FILTER_SELECT_CLASS,
  PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS,
  type PageFilterBarProps,
  type PageFilterField,
} from './types';

/**
 * Search submits on Enter; select/date fire immediately on change.
 * Matches the pre-existing Provider Orders Index / Recommended / Offers
 * filter bars (Enter-only search + onChange selects/dates) — not a
 * generalization invent. Clear (X) is the exception: it applies immediately.
 */
function SearchFilterField({
  field,
  onFilterChange,
}: {
  field: PageFilterField;
  onFilterChange: PageFilterBarProps['onFilterChange'];
}) {
  const [value, setValue] = useState(field.value ?? '');

  useEffect(() => {
    setValue(field.value ?? '');
  }, [field.value]);

  const showClear = shouldShowSearchClearButton(value);

  return (
    <div className={PAGE_FILTER_SEARCH_FIELD_CLASS}>
      <KTIcon iconName="magnifier" className={PAGE_FILTER_SEARCH_ICON_CLASS} />
      <input
        type="text"
        name={field.name}
        value={value}
        data-kt-user-table-filter="search"
        className={field.className ?? PAGE_FILTER_SEARCH_INPUT_CLASS}
        placeholder={field.placeholder}
        onChange={(event) => {
          setValue(event.currentTarget.value);
        }}
        onKeyDown={(event) => {
          if (event.key === 'Enter') {
            onFilterChange(field.name, event.currentTarget.value);
          }
        }}
      />
      {showClear ? (
        <button
          type="button"
          className={PAGE_FILTER_SEARCH_CLEAR_BUTTON_CLASS}
          aria-label="Clear"
          data-kt-search-element="clear"
          onClick={() => {
            setValue(clearSearchFilterValue(field.name, onFilterChange));
          }}
        >
          <KTIcon iconName="cross" className="fs-2" />
        </button>
      ) : null}
    </div>
  );
}

function SelectFilterField({
  field,
  onFilterChange,
}: {
  field: PageFilterField;
  onFilterChange: PageFilterBarProps['onFilterChange'];
}) {
  return (
    <div className={field.widthClassName ?? PAGE_FILTER_SELECT_DEFAULT_WIDTH_CLASS}>
      <select
        name={field.name}
        data-control="select2"
        data-hide-search="true"
        className={field.className ?? PAGE_FILTER_SELECT_CLASS}
        defaultValue={field.value ?? ''}
        onChange={(event) => onFilterChange(field.name, event.target.value)}
      >
        {(field.options ?? []).map((option) => (
          <option key={`${field.name}-${option.value}`} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
}

function DateFilterField({
  field,
  onFilterChange,
}: {
  field: PageFilterField;
  onFilterChange: PageFilterBarProps['onFilterChange'];
}) {
  const label = field.label?.trim() ?? '';

  return (
    <div className={field.widthClassName ?? PAGE_FILTER_DATE_DEFAULT_WIDTH_CLASS}>
      {label !== '' ? (
        <label className={PAGE_FILTER_DATE_LABEL_CLASS} htmlFor={`page-filter-${field.name}`}>
          {label}
        </label>
      ) : null}
      <input
        id={`page-filter-${field.name}`}
        type="date"
        name={field.name}
        className={field.className ?? PAGE_FILTER_DATE_CLASS}
        placeholder={field.placeholder}
        defaultValue={field.value ?? ''}
        onChange={(event) => onFilterChange(field.name, event.target.value)}
      />
    </div>
  );
}

export default function PageFilterBar({
  filters,
  onFilterChange,
  className,
}: PageFilterBarProps) {
  const searchFields = filters.filter((field) => field.type === 'search');
  const controlFields = filters.filter((field) => field.type !== 'search');
  const rootClass = [PAGE_FILTER_BAR_CLASS, className].filter(Boolean).join(' ');

  return (
    <div className={rootClass}>
      <div className={PAGE_FILTER_SEARCH_COLUMN_CLASS}>
        {searchFields.map((field) => (
          <SearchFilterField
            key={field.name}
            field={field}
            onFilterChange={onFilterChange}
          />
        ))}
      </div>

      {controlFields.length > 0 ? (
        <div className={PAGE_FILTER_CONTROLS_CLASS}>
          {controlFields.map((field) => {
            if (field.type === 'select') {
              return (
                <SelectFilterField
                  key={field.name}
                  field={field}
                  onFilterChange={onFilterChange}
                />
              );
            }

            if (field.type === 'date') {
              return (
                <DateFilterField
                  key={field.name}
                  field={field}
                  onFilterChange={onFilterChange}
                />
              );
            }

            return null;
          })}
        </div>
      ) : null}
    </div>
  );
}
